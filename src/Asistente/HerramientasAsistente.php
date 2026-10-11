<?php

declare(strict_types=1);

namespace App\Asistente;

use App\Institucion\Configuracion;
use App\Institucion\Evaluacion\RecordAcademico;
use PDO;

/**
 * Las herramientas del asistente: consultas fijas, de solo lectura y acotadas por la sesión (el modelo nunca
 * elige de quién son los datos). Devuelven lo mínimo: nombres y cifras; nunca DNI, teléfonos, direcciones ni
 * datos de salud.
 *
 * - ADMINISTRADOR: toda la institución.
 * - SECRETARIA: alumnos, récord académico y resumen (sin pagos).
 * - DOCENTE: solo las aulas y cursos donde dicta.
 * - ESTUDIANTE: solo sus matrículas.
 */
final class HerramientasAsistente implements EjecutorHerramientas
{
    private const ROLES = [
        'resumen_institucion' => ['ADMINISTRADOR', 'SECRETARIA'],
        'pensiones_vencidas' => ['ADMINISTRADOR', 'ESTUDIANTE'],
        'asistencia_resumen' => ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'],
        'notas_resumen' => ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'],
        'buscar_alumno' => ['ADMINISTRADOR', 'SECRETARIA', 'DOCENTE'],
        'record_academico' => ['ADMINISTRADOR', 'SECRETARIA', 'ESTUDIANTE'],
        'catalogo' => ['ADMINISTRADOR', 'SECRETARIA', 'DOCENTE', 'ESTUDIANTE'],
    ];

    private const NOTA_NUMERICA = "n.nota REGEXP '^[0-9]+([.][0-9]+)?$'";

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $rol,
        private readonly int $usuId,
        private readonly Configuracion $configuracion,
    ) {
    }

    public function definiciones(): array
    {
        $texto = static fn (string $d): array => ['type' => 'string', 'description' => $d];
        $limite = ['type' => 'integer', 'description' => 'Máximo de filas (por defecto 30, tope 100).'];
        $fecha = static fn (string $d): array => ['type' => 'string', 'description' => "$d (AAAA-MM-DD)."];
        $todas = [
            'resumen_institucion' => ['Cifras generales del año escolar actual: alumnos activos, matriculados por nivel, docentes y, '
                . 'para el administrador, ingresos y egresos del mes.', []],
            'pensiones_vencidas' => ['Pensiones vencidas y no pagadas del año actual, por alumno (meses y monto). El estudiante solo ve '
                . 'las suyas.', ['grado' => $texto('Filtra por aula o grado (texto parcial, p. ej. «3RO»).'), 'limite' => $limite]],
            'asistencia_resumen' => ['Faltas, tardanzas y justificaciones por alumno en un rango de fechas (por defecto, los últimos 30 '
                . 'días), ordenado de más a menos faltas.', ['desde' => $fecha('Inicio'), 'hasta' => $fecha('Fin'),
                    'grado' => $texto('Filtra por aula o grado (texto parcial).'), 'limite' => $limite]],
            'notas_resumen' => ['Notas registradas. modo «por_curso»: promedio y desaprobados por aula, curso y periodo; modo '
                . '«por_alumno»: promedio de cada alumno por curso. Las notas literales (AD, A, B, C) se cuentan aparte.',
                ['modo' => ['type' => 'string', 'enum' => ['por_curso', 'por_alumno']], 'grado' => $texto('Filtra por aula o grado.'),
                    'curso' => $texto('Filtra por nombre del curso (texto parcial).'), 'periodo' => $texto('Filtra por periodo (p. ej. «I BIMESTRE»).'),
                    'limite' => $limite]],
            'buscar_alumno' => ['Busca alumnos por nombre o apellido: estado y aula del año actual.', ['nombre' => $texto('Al menos 3 letras.')]],
            'record_academico' => ['Récord académico en un instituto: promedio, créditos aprobados y unidades pendientes (cargos) por '
                . 'programa. El estudiante ve el suyo; los demás indican el nombre del alumno.', ['nombre' => $texto('Nombre del alumno.')]],
            'catalogo' => ['Aulas, cursos y periodos disponibles para este usuario (para precisar filtros).', []],
        ];
        $definiciones = [];
        foreach ($todas as $nombre => [$descripcion, $propiedades]) {
            if (!in_array($this->rol, self::ROLES[$nombre], true)) {
                continue;
            }
            if ($nombre === 'record_academico' && !$this->configuracion->matriculaPorUnidad()) {
                continue;
            }
            $definiciones[] = [
                'name' => $nombre,
                'description' => $descripcion,
                'inputSchema' => ['type' => 'object', 'properties' => $propiedades === [] ? new \stdClass() : $propiedades],
            ];
        }
        return $definiciones;
    }

    public function ejecutar(string $nombre, array $entrada): array
    {
        if (!isset(self::ROLES[$nombre]) || !in_array($this->rol, self::ROLES[$nombre], true)) {
            return ['error' => 'Herramienta no disponible para este usuario.'];
        }
        return match ($nombre) {
            'resumen_institucion' => $this->resumen(),
            'pensiones_vencidas' => $this->pensiones($entrada),
            'asistencia_resumen' => $this->asistencia($entrada),
            'notas_resumen' => $this->notas($entrada),
            'buscar_alumno' => $this->buscar($entrada),
            'record_academico' => $this->record($entrada),
            default => $this->catalogo(),
        };
    }

    // ---------------------------------------------------------------- utilidades

    /** @param array<string, mixed> $e */
    private static function texto(array $e, string $campo, int $max = 60): string
    {
        $v = $e[$campo] ?? '';
        return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
    }

    /** @param array<string, mixed> $e */
    private static function limite(array $e): int
    {
        $v = $e['limite'] ?? 30;
        return is_numeric($v) ? max(1, min(100, (int) $v)) : 30;
    }

    /** @param array<string, mixed> $e */
    private static function fecha(array $e, string $campo): ?string
    {
        $v = self::texto($e, $campo, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 && strtotime($v) !== false ? $v : null;
    }

    /** Búsqueda sin distinguir mayúsculas ni la colación de cada columna heredada (algunas son utf8_bin). */
    private static function parecido(string $expresion): string
    {
        return "CONVERT($expresion USING utf8mb4) COLLATE utf8mb4_general_ci LIKE ?";
    }

    private static function como(string $t): string
    {
        return '%' . addcslashes($t, '%_\\') . '%';
    }

    private function anio(): int
    {
        $id = $this->pdo->query("SELECT COALESCE(
                (SELECT Id_año_escolar FROM año_escolar WHERE año_escolar = YEAR(CURDATE()) ORDER BY Id_año_escolar DESC LIMIT 1),
                (SELECT MAX(Id_año_escolar) FROM año_escolar))")->fetchColumn();
        return (int) $id;
    }

    /**
     * Condición de alcance sobre la matrícula «m» según el rol.
     *
     * @return array{0: string, 1: list<int>}
     */
    private function alcance(): array
    {
        return match ($this->rol) {
            'ESTUDIANTE' => [' AND m.usu_id = ?', [$this->usuId]],
            'DOCENTE' => [' AND m.id_aula IN (SELECT a.Id_grado FROM detalle_asignatura_docente dad
                    JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente
                    JOIN docentes d ON d.Id_docente = ad.Id_docente
                    JOIN asignaturas a ON a.Id_asignatura = dad.Id_asignatura
                   WHERE d.id_asusuario = ?)', [$this->usuId]],
            default => ['', []],
        };
    }

    /**
     * @param list<mixed> $parametros
     * @return list<array<string, mixed>>
     */
    private function filas(string $sql, array $parametros): array
    {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------------------------------------------------------------- herramientas

    /** @return array<string, mixed> */
    private function resumen(): array
    {
        $anio = $this->anio();
        $datos = [
            'año_escolar' => $this->pdo->query("SELECT año_escolar FROM año_escolar WHERE Id_año_escolar = $anio")->fetchColumn(),
            'alumnos_activos' => (int) $this->pdo->query("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'")->fetchColumn(),
            'docentes_activos' => (int) $this->pdo->query("SELECT COUNT(*) FROM docentes WHERE docente_estatus = 'ACTIVO'")->fetchColumn(),
            'matriculados_por_nivel' => $this->filas(
                'SELECT n.Nivel_academico AS nivel, COUNT(*) AS matriculados FROM matricula m
                   JOIN aulas a ON a.Id_aula = m.id_aula JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                  WHERE m.id_año = ? GROUP BY n.Nivel_academico ORDER BY n.Nivel_academico',
                [$anio]
            ),
        ];
        if ($this->rol === 'ADMINISTRADOR') {
            $mes = "created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND created_at <= LAST_DAY(CURDATE())";
            $datos['ingresos_del_mes'] = (float) $this->pdo->query("SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE estado = 'VALIDO' AND $mes")->fetchColumn();
            $datos['egresos_del_mes'] = (float) $this->pdo->query("SELECT COALESCE(SUM(monto), 0) FROM egresos WHERE estado = 'VALIDO' AND $mes")->fetchColumn();
            $datos['moneda'] = 'PEN';
        }
        return $datos;
    }

    /**
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private function pensiones(array $e): array
    {
        [$condicion, $parametros] = $this->alcance();
        $grado = self::texto($e, 'grado');
        $sql = "SELECT CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno, au.Grado AS aula, n.Nivel_academico AS nivel,
                       COUNT(*) AS meses_vencidos, SUM(p.precio) AS monto,
                       GROUP_CONCAT(p.mes ORDER BY p.fecha_vencimiento SEPARATOR ', ') AS meses
                  FROM matricula m
                  JOIN alumnos al ON al.Id_alumno = m.id_alumno
                  JOIN aulas au ON au.Id_aula = m.id_aula
                  JOIN nivel_academico n ON n.Id_nivel = au.id_nivel_academico
                  JOIN año_escolar y ON y.Id_año_escolar = m.id_año
                  JOIN pensiones p ON p.id_nivel_academico = au.id_nivel_academico
                   AND (p.fecha_vencimiento BETWEEN y.fecha_inicio AND y.fecha_fin OR YEAR(p.fecha_vencimiento) = y.año_escolar)
                   AND p.fecha_vencimiento < CURDATE()
                 WHERE m.id_año = ?" . $condicion . ($grado !== '' ? ' AND ' . self::parecido('au.Grado') : '') . "
                   AND NOT EXISTS (SELECT 1 FROM pago_pensiones pp WHERE pp.id_matri = m.id_matricula AND pp.id_pension = p.id_pensiones)
                 GROUP BY m.id_matricula ORDER BY monto DESC, alumno LIMIT " . self::limite($e);
        $filas = $this->filas($sql, array_merge([$this->anio()], $parametros, $grado !== '' ? [self::como($grado)] : []));
        return ['alumnos_con_deuda' => count($filas), 'total' => round(array_sum(array_map(static fn (array $f): float => (float) $f['monto'], $filas)), 2),
            'moneda' => 'PEN', 'detalle' => $filas,
            'nota' => 'Estimado con las pensiones del nivel ya vencidas y sin pago registrado; no incluye moras.'];
    }

    /**
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private function asistencia(array $e): array
    {
        [$condicion, $parametros] = $this->alcance();
        $hasta = self::fecha($e, 'hasta') ?? (string) $this->pdo->query('SELECT CURDATE()')->fetchColumn();
        $desde = self::fecha($e, 'desde') ?? date('Y-m-d', (int) strtotime("$hasta -30 days"));
        $grado = self::texto($e, 'grado');
        $sql = "SELECT CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno, au.Grado AS aula,
                       SUM(a.estado = 'AUSENTE') AS faltas, SUM(a.estado = 'TARDE') AS tardanzas,
                       SUM(a.estado = 'JUSTIFICADO') AS justificadas, COUNT(*) AS dias_registrados
                  FROM asistencia a
                  JOIN matricula m ON m.id_matricula = a.id_matricula
                  JOIN alumnos al ON al.Id_alumno = m.id_alumno
                  JOIN aulas au ON au.Id_aula = m.id_aula
                 WHERE a.fecha BETWEEN ? AND ?" . $condicion . ($grado !== '' ? ' AND ' . self::parecido('au.Grado') : '') . "
                 GROUP BY m.id_matricula ORDER BY faltas DESC, tardanzas DESC, alumno LIMIT " . self::limite($e);
        return ['desde' => $desde, 'hasta' => $hasta,
            'detalle' => $this->filas($sql, array_merge([$desde, $hasta], $parametros, $grado !== '' ? [self::como($grado)] : []))];
    }

    /**
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private function notas(array $e): array
    {
        [$condicion, $parametros] = $this->alcance();
        if ($this->rol === 'DOCENTE') {
            // Además del aula, solo sus cursos.
            $condicion .= ' AND dad.Id_detalle_asig_docente IN (SELECT dad2.Id_detalle_asig_docente FROM detalle_asignatura_docente dad2
                JOIN asignatura_docente ad ON ad.Id_asigdocente = dad2.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
               WHERE d.id_asusuario = ?)';
            $parametros[] = $this->usuId;
        }
        $filtros = '';
        foreach (['grado' => 'au.Grado', 'curso' => 'asig.nombre_asig', 'periodo' => 'pe.periodos'] as $campo => $columna) {
            $valor = self::texto($e, $campo);
            if ($valor !== '') {
                $filtros .= ' AND ' . self::parecido($columna);
                $parametros[] = self::como($valor);
            }
        }
        $minima = $this->configuracion->notaMinima();
        $porAlumno = ($e['modo'] ?? '') === 'por_alumno' || $this->rol === 'ESTUDIANTE';
        $grupo = $porAlumno
            ? "CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno, au.Grado AS aula, asig.nombre_asig AS curso, pe.periodos AS periodo"
            : 'au.Grado AS aula, asig.nombre_asig AS curso, pe.periodos AS periodo, COUNT(DISTINCT m.id_matricula) AS alumnos';
        $agrupar = $porAlumno ? 'm.id_matricula, asig.Id_asignatura, pe.id_periodo' : 'au.Id_aula, asig.Id_asignatura, pe.id_periodo';
        $sql = "SELECT $grupo,
                       ROUND(AVG(CASE WHEN " . self::NOTA_NUMERICA . " THEN n.nota + 0 END), 2) AS promedio,
                       SUM(CASE WHEN " . self::NOTA_NUMERICA . " AND n.nota + 0 < ? THEN 1 WHEN UPPER(n.nota) = 'C' THEN 1 ELSE 0 END) AS notas_desaprobadas,
                       COUNT(*) AS notas_registradas,
                       GROUP_CONCAT(DISTINCT CASE WHEN NOT " . self::NOTA_NUMERICA . " THEN UPPER(TRIM(n.nota)) END) AS notas_literales
                  FROM notas n
                  JOIN matricula m ON m.id_matricula = n.id_matricula
                  JOIN alumnos al ON al.Id_alumno = m.id_alumno
                  JOIN aulas au ON au.Id_aula = m.id_aula
                  JOIN criterios c ON c.id_criterio = n.id_criterio
                  JOIN detalle_asignatura_docente dad ON dad.Id_detalle_asig_docente = c.id_detalle_asignatura
                  JOIN asignaturas asig ON asig.Id_asignatura = dad.Id_asignatura
                  LEFT JOIN periodos pe ON pe.id_periodo = n.id_bimestre
                 WHERE TRIM(n.nota) <> ''" . $condicion . $filtros . "
                 GROUP BY $agrupar ORDER BY AVG(CASE WHEN " . self::NOTA_NUMERICA . " THEN n.nota + 0 END) IS NULL, promedio LIMIT " . self::limite($e);
        return ['nota_minima_aprobatoria' => $minima, 'modo' => $porAlumno ? 'por_alumno' : 'por_curso',
            'detalle' => $this->filas($sql, array_merge([$minima], $parametros))];
    }

    /**
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private function buscar(array $e): array
    {
        $nombre = self::texto($e, 'nombre');
        if (mb_strlen($nombre) < 3) {
            return ['error' => 'Indica al menos 3 letras del nombre.'];
        }
        $docente = $this->rol === 'DOCENTE';
        [$condicion, $parametros] = $docente ? $this->alcance() : ['', []];
        $sql = "SELECT CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno,
                       IF(al.alum_estatus = 'SI', 'ACTIVO', 'INACTIVO') AS estado, au.Grado AS aula, n.Nivel_academico AS nivel
                  FROM alumnos al
                  LEFT JOIN matricula m ON m.id_matricula = (SELECT MAX(x.id_matricula) FROM matricula x WHERE x.id_alumno = al.Id_alumno AND x.id_año = ?)
                  LEFT JOIN aulas au ON au.Id_aula = m.id_aula
                  LEFT JOIN nivel_academico n ON n.Id_nivel = au.id_nivel_academico
                 WHERE " . self::parecido("CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat)") . ($docente ? ' AND m.id_matricula IS NOT NULL' . $condicion : '') . '
                 ORDER BY al.alum_apepat, al.alum_nombre LIMIT 20';
        return ['coincidencias' => $this->filas($sql, array_merge([$this->anio(), self::como($nombre)], $parametros))];
    }

    /**
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private function record(array $e): array
    {
        if (!$this->configuracion->matriculaPorUnidad()) {
            return ['error' => 'El récord académico por unidades es de institutos.'];
        }
        if ($this->rol === 'ESTUDIANTE') {
            $alumnos = $this->filas("SELECT DISTINCT al.Id_alumno AS id, CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno
                FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno WHERE m.usu_id = ?", [$this->usuId]);
        } else {
            $nombre = self::texto($e, 'nombre');
            if (mb_strlen($nombre) < 3) {
                return ['error' => 'Indica el nombre del alumno (al menos 3 letras).'];
            }
            $alumnos = $this->filas("SELECT Id_alumno AS id, CONCAT_WS(' ', alum_nombre, alum_apepat, alum_apemat) AS alumno FROM alumnos
                WHERE " . self::parecido("CONCAT_WS(' ', alum_nombre, alum_apepat, alum_apemat)") . ' ORDER BY alum_apepat LIMIT 6', [self::como($nombre)]);
        }
        if ($alumnos === []) {
            return ['error' => 'No se encontró al alumno.'];
        }
        if (count($alumnos) > 1 && $this->rol !== 'ESTUDIANTE') {
            return ['varios_alumnos' => array_column($alumnos, 'alumno'), 'nota' => 'Pide un nombre más preciso.'];
        }
        $record = new RecordAcademico($this->pdo);
        $resultado = [];
        foreach ($alumnos as $a) {
            $programas = $this->filas('SELECT DISTINCT p.id_programa, p.nombre FROM matricula_unidades mu
                JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad JOIN modulos_formativos mo ON mo.id_modulo = u.id_modulo
                JOIN programas_estudio p ON p.id_programa = mo.id_programa WHERE mu.id_alumno = ?', [(int) $a['id']]);
            foreach ($programas as $p) {
                $r = $record->de((int) $a['id'], (int) $p['id_programa'], $this->configuracion);
                $resultado[] = ['alumno' => $a['alumno'], 'programa' => $p['nombre'], 'criterio_promedio' => $r['estrategia'],
                    'promedio' => $r['promedio'], 'creditos_aprobados' => $r['creditos_aprobados'], 'unidades_aprobadas' => $r['unidades_aprobadas'],
                    'cargos' => array_map(static fn (array $c): array => ['unidad' => $c['nombre'], 'nota' => $c['nota'], 'puede_recuperar' => $c['recuperable']], $r['cargos'])];
            }
        }
        return ['records' => $resultado, 'nota_minima_aprobatoria' => $this->configuracion->notaMinima()];
    }

    /** @return array<string, mixed> */
    private function catalogo(): array
    {
        [$condicion, $parametros] = $this->alcance();
        $aulas = $this->rol === 'ADMINISTRADOR' || $this->rol === 'SECRETARIA'
            ? $this->filas("SELECT a.Grado AS aula, n.Nivel_academico AS nivel FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                WHERE a.estado = 'ACTIVO' ORDER BY n.Nivel_academico, a.Grado", [])
            : $this->filas(
                'SELECT DISTINCT au.Grado AS aula FROM matricula m JOIN aulas au ON au.Id_aula = m.id_aula WHERE m.id_año = ?' . $condicion,
                array_merge([$this->anio()], $parametros)
            );
        $periodos = $this->filas('SELECT p.periodos AS periodo, p.estado FROM periodos p WHERE p.id_año_escolar = ? ORDER BY p.fecha_inicio', [$this->anio()]);
        $cursos = $this->rol === 'DOCENTE'
            ? $this->filas('SELECT DISTINCT asig.nombre_asig AS curso, au.Grado AS aula FROM detalle_asignatura_docente dad
                JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
                JOIN asignaturas asig ON asig.Id_asignatura = dad.Id_asignatura LEFT JOIN aulas au ON au.Id_aula = asig.Id_grado
               WHERE d.id_asusuario = ?', [$this->usuId])
            : [];
        return ['aulas' => $aulas, 'periodos_del_año' => $periodos] + ($cursos !== [] ? ['mis_cursos' => $cursos] : []);
    }
}
