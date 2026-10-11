<?php

declare(strict_types=1);

namespace App\Institucion;

use PDO;

/**
 * El tablero de inicio de un instituto: el semestre en curso (matriculados, unidades, créditos, aprobación,
 * cargos) y lo que requiere atención (unidades sin docente, las de menor aprobación).
 */
final class TableroInstituto
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** El periodo en curso por fechas; si no hay, el último que ya empezó; si tampoco, el más reciente. */
    public function periodoActual(): ?array
    {
        $fila = $this->pdo->query(
            "SELECT p.id_periodo, CONCAT(a.Nombre_año, ' · ', p.periodos) AS nombre
               FROM periodos p JOIN año_escolar a ON a.Id_año_escolar = p.id_año_escolar
              ORDER BY (CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin) DESC, (p.fecha_inicio <= CURDATE()) DESC, p.fecha_inicio DESC
              LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        return is_array($fila) ? ['id' => (int) $fila['id_periodo'], 'nombre' => (string) $fila['nombre']] : null;
    }

    /** @return array<string, mixed> */
    public function resumen(): array
    {
        $periodo = $this->periodoActual();
        $p = $periodo['id'] ?? 0;
        $uno = function (string $sql, array $parametros = []): mixed {
            $consulta = $this->pdo->prepare($sql);
            $consulta->execute($parametros);
            return $consulta->fetchColumn();
        };
        $filas = function (string $sql, array $parametros = []): array {
            $consulta = $this->pdo->prepare($sql);
            $consulta->execute($parametros);
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        };
        $calificadas = $filas("SELECT SUM(estado = 'APROBADO') AS aprobadas, SUM(estado = 'DESAPROBADO') AS desaprobadas
            FROM matricula_unidades WHERE id_periodo = ?", [$p])[0] ?? [];
        $aprobadas = (int) ($calificadas['aprobadas'] ?? 0);
        $desaprobadas = (int) ($calificadas['desaprobadas'] ?? 0);
        return [
            'periodo' => $periodo,
            'estudiantes_activos' => (int) $uno("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'"),
            'docentes_activos' => (int) $uno("SELECT COUNT(*) FROM docentes WHERE docente_estatus = 'ACTIVO'"),
            'programas' => (int) $uno("SELECT COUNT(*) FROM programas_estudio WHERE estado = 'ACTIVO'"),
            'unidades' => (int) $uno("SELECT COUNT(*) FROM unidades_didacticas WHERE estado = 'ACTIVO'"),
            'matriculados' => (int) $uno("SELECT COUNT(DISTINCT id_alumno) FROM matricula_unidades WHERE id_periodo = ? AND estado <> 'RETIRADO'", [$p]),
            'unidades_en_curso' => (int) $uno("SELECT COUNT(DISTINCT id_unidad) FROM matricula_unidades WHERE id_periodo = ? AND estado = 'MATRICULADO'", [$p]),
            'creditos_promedio' => round((float) $uno(
                "SELECT AVG(t.creditos) FROM (SELECT SUM(u.creditos) AS creditos FROM matricula_unidades mu
                   JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad
                  WHERE mu.id_periodo = ? AND mu.estado <> 'RETIRADO' GROUP BY mu.id_alumno) t",
                [$p]
            ), 1),
            'aprobacion' => $aprobadas + $desaprobadas > 0 ? round(100 * $aprobadas / ($aprobadas + $desaprobadas), 1) : null,
            'por_calificar' => (int) $uno("SELECT COUNT(*) FROM matricula_unidades WHERE id_periodo = ? AND estado = 'MATRICULADO'", [$p]),
            // Cargo: el último intento de la unidad quedó desaprobado.
            'cargos' => (int) $uno(
                "SELECT COUNT(*) FROM matricula_unidades mu
                  WHERE mu.estado = 'DESAPROBADO' AND mu.id_matricula_unidad = (SELECT MAX(x.id_matricula_unidad) FROM matricula_unidades x
                        WHERE x.id_alumno = mu.id_alumno AND x.id_unidad = mu.id_unidad AND x.estado <> 'RETIRADO')"
            ),
            'por_programa' => $filas(
                "SELECT p.nombre AS programa, COUNT(DISTINCT mu.id_alumno) AS matriculados
                   FROM programas_estudio p
                   LEFT JOIN modulos_formativos mo ON mo.id_programa = p.id_programa
                   LEFT JOIN unidades_didacticas u ON u.id_modulo = mo.id_modulo
                   LEFT JOIN matricula_unidades mu ON mu.id_unidad = u.id_unidad AND mu.id_periodo = ? AND mu.estado <> 'RETIRADO'
                  WHERE p.estado = 'ACTIVO' GROUP BY p.id_programa ORDER BY matriculados DESC, p.nombre",
                [$p]
            ),
            'sin_docente' => $filas(
                "SELECT u.codigo, u.nombre, COUNT(DISTINCT mu.id_alumno) AS matriculados
                   FROM matricula_unidades mu JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad
                  WHERE mu.id_periodo = ? AND mu.estado <> 'RETIRADO'
                    AND NOT EXISTS (SELECT 1 FROM unidades_docentes ud WHERE ud.id_unidad = mu.id_unidad AND ud.id_periodo = mu.id_periodo)
                  GROUP BY u.id_unidad ORDER BY matriculados DESC LIMIT 8",
                [$p]
            ),
            'menor_aprobacion' => $filas(
                "SELECT u.codigo, u.nombre, SUM(mu.estado = 'APROBADO') AS aprobados, SUM(mu.estado = 'DESAPROBADO') AS desaprobados,
                        ROUND(100 * SUM(mu.estado = 'APROBADO') / COUNT(*), 1) AS aprobacion
                   FROM matricula_unidades mu JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad
                  WHERE mu.id_periodo = ? AND mu.estado IN ('APROBADO', 'DESAPROBADO')
                  GROUP BY u.id_unidad ORDER BY aprobacion, desaprobados DESC LIMIT 6",
                [$p]
            ),
            'ingresos_del_mes' => (float) $uno("SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO'
                AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
        ];
    }
}
