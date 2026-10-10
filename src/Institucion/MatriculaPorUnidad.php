<?php

declare(strict_types=1);

namespace App\Institucion;

use PDO;

/**
 * Matrícula por unidad didáctica (Fase 5.4): un alumno se matricula en unidades concretas en un periodo,
 * siempre que tenga aprobados sus prerrequisitos (lo decide SP_MATRICULAR_UNIDAD).
 */
final class MatriculaPorUnidad
{
    /** Lo que responde SP_MATRICULAR_UNIDAD, para el usuario. */
    public const RESULTADOS = [
        1 => 'Matriculado.',
        2 => 'Ya está matriculado en esa unidad en este periodo.',
        3 => 'Ya aprobó esa unidad.',
        4 => 'Le falta aprobar un prerrequisito de esa unidad.',
        0 => 'El alumno, la unidad o el periodo no existe, o la unidad está inactiva.',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function matricular(int $alumno, int $unidad, int $periodo): int
    {
        $consulta = $this->pdo->prepare('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)');
        $consulta->execute([$alumno, $unidad, $periodo]);
        $resultado = (int) $consulta->fetchColumn();
        $consulta->closeCursor();
        return $resultado;
    }

    public function retirar(int $idMatriculaUnidad): bool
    {
        $consulta = $this->pdo->prepare('CALL SP_RETIRAR_UNIDAD(?)');
        $consulta->execute([$idMatriculaUnidad]);
        $ok = (int) $consulta->fetchColumn() === 1;
        $consulta->closeCursor();
        return $ok;
    }

    /**
     * Las unidades de un programa vistas desde un alumno en un periodo: qué ya aprobó, en qué está matriculado
     * y qué puede llevar (o qué prerrequisito le falta).
     *
     * @return list<array<string, mixed>>
     */
    public function situacion(int $alumno, int $programa, int $periodo): array
    {
        $unidades = $this->pdo->prepare(
            "SELECT u.id_unidad, u.codigo, u.nombre, u.periodo_academico, u.creditos, u.estado AS estado_unidad, m.nombre AS modulo
               FROM unidades_didacticas u JOIN modulos_formativos m ON m.id_modulo = u.id_modulo
              WHERE m.id_programa = ? ORDER BY u.periodo_academico, u.codigo"
        );
        $unidades->execute([$programa]);
        $historial = $this->pdo->prepare('SELECT id_matricula_unidad, id_unidad, id_periodo, estado, nota_final FROM matricula_unidades WHERE id_alumno = ? ORDER BY id_matricula_unidad');
        $historial->execute([$alumno]);
        $aprobadas = [];
        $enPeriodo = [];
        foreach ($historial->fetchAll(PDO::FETCH_ASSOC) as $h) {
            if ($h['estado'] === 'APROBADO') {
                $aprobadas[(int) $h['id_unidad']] = $h;
            }
            if ((int) $h['id_periodo'] === $periodo) {
                $enPeriodo[(int) $h['id_unidad']] = $h;
            }
        }
        $requisitos = [];
        foreach ($this->pdo->query('SELECT p.id_unidad, p.id_requisito, u.codigo FROM prerrequisitos p JOIN unidades_didacticas u ON u.id_unidad = p.id_requisito')->fetchAll(PDO::FETCH_NUM) as [$u, $r, $codigo]) {
            $requisitos[(int) $u][(int) $r] = (string) $codigo;
        }
        $situacion = [];
        foreach ($unidades->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $id = (int) $u['id_unidad'];
            $faltan = array_values(array_diff_key($requisitos[$id] ?? [], $aprobadas));
            $estado = match (true) {
                isset($aprobadas[$id]) => 'APROBADA',
                isset($enPeriodo[$id]) => $enPeriodo[$id]['estado'],
                $u['estado_unidad'] !== 'ACTIVO' => 'INACTIVA',
                $faltan !== [] => 'FALTA_REQUISITO',
                default => 'DISPONIBLE',
            };
            $situacion[] = $u + [
                'situacion' => $estado,
                'faltan' => $faltan,
                'id_matricula_unidad' => isset($enPeriodo[$id]) ? (int) $enPeriodo[$id]['id_matricula_unidad'] : null,
                'nota_final' => $aprobadas[$id]['nota_final'] ?? ($enPeriodo[$id]['nota_final'] ?? null),
            ];
        }
        return $situacion;
    }
}
