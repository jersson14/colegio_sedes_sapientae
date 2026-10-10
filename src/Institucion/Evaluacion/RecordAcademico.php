<?php

declare(strict_types=1);

namespace App\Institucion\Evaluacion;

use App\Institucion\Configuracion;
use PDO;

/**
 * Récord académico de un alumno en un programa (Fase 5.5 y 5.6): por unidad, su último intento y la nota
 * que cuenta (la de recuperación si la rindió); créditos aprobados; unidades pendientes («cargos»: las
 * desaprobadas que aún no aprobó ni está llevando de nuevo) y el promedio según la estrategia de la institución.
 */
final class RecordAcademico
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function estrategia(Configuracion $c): EstrategiaEvaluacion
    {
        return $c->ponderaPorCreditos() ? new PromedioPorCreditos() : new PromedioSimple();
    }

    /** @return array{estrategia: string, promedio: ?float, creditos_aprobados: float, unidades_aprobadas: int, cargos: list<array<string, mixed>>} */
    public function de(int $alumno, int $programa, Configuracion $configuracion): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT mu.id_matricula_unidad, mu.id_unidad, mu.estado, mu.nota_final, mu.nota_recuperacion, u.codigo, u.nombre, u.creditos
               FROM matricula_unidades mu
               JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad
               JOIN modulos_formativos m ON m.id_modulo = u.id_modulo
              WHERE mu.id_alumno = ? AND m.id_programa = ? AND mu.estado <> 'RETIRADO'
              ORDER BY mu.id_matricula_unidad"
        );
        $consulta->execute([$alumno, $programa]);
        $ultimo = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $intento) {
            $ultimo[(int) $intento['id_unidad']] = $intento; // por orden de matrícula: queda el más reciente
        }
        $calificaciones = [];
        $creditosAprobados = 0.0;
        $aprobadas = 0;
        $cargos = [];
        foreach ($ultimo as $u) {
            if ($u['estado'] === 'MATRICULADO') {
                continue; // en curso: aún no cuenta
            }
            $nota = (float) ($u['nota_recuperacion'] ?? $u['nota_final']);
            $calificaciones[] = ['nota' => $nota, 'creditos' => (float) $u['creditos']];
            if ($u['estado'] === 'APROBADO') {
                $creditosAprobados += (float) $u['creditos'];
                $aprobadas++;
            } else {
                $cargos[] = ['codigo' => $u['codigo'], 'nombre' => $u['nombre'], 'nota' => $nota,
                    'recuperable' => $u['nota_recuperacion'] === null && $nota >= $configuracion->recuperacionDesde() && $nota < $configuracion->notaMinima()];
            }
        }
        $estrategia = self::estrategia($configuracion);
        return [
            'estrategia' => $estrategia->nombre(),
            'promedio' => $estrategia->promedio($calificaciones),
            'creditos_aprobados' => round($creditosAprobados, 1),
            'unidades_aprobadas' => $aprobadas,
            'cargos' => $cargos,
        ];
    }
}
