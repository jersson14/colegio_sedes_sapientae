<?php

declare(strict_types=1);

namespace App\Institucion;

use PDO;

/**
 * Docente de cada unidad didáctica por periodo (Fase 5.9). La secretaría o el administrador asignan; el
 * docente ve sus unidades del periodo y califica solo a quienes están matriculados en ellas.
 */
final class DocentesPorUnidad
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** 1 asignado o quitado (docente 0); 0 si la unidad, el periodo o el docente no existen (o está inactivo). */
    public function asignar(int $unidad, int $periodo, int $docente): int
    {
        $consulta = $this->pdo->prepare('CALL SP_ASIGNAR_DOCENTE_UNIDAD(?, ?, ?)');
        $consulta->execute([$unidad, $periodo, $docente]);
        $resultado = (int) $consulta->fetchColumn();
        $consulta->closeCursor();
        return $resultado;
    }

    /**
     * Las unidades activas de un programa con su docente en el periodo (o sin docente).
     *
     * @return list<array<string, mixed>>
     */
    public function asignaciones(int $programa, int $periodo): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT u.id_unidad, u.codigo, u.nombre, u.periodo_academico, m.nombre AS modulo, ud.id_docente,
                    CONCAT_WS(' ', d.docente_nombre, d.docente_apelli) AS docente
               FROM unidades_didacticas u
               JOIN modulos_formativos m ON m.id_modulo = u.id_modulo
               LEFT JOIN unidades_docentes ud ON ud.id_unidad = u.id_unidad AND ud.id_periodo = ?
               LEFT JOIN docentes d ON d.Id_docente = ud.id_docente
              WHERE m.id_programa = ? AND u.estado = 'ACTIVO'
              ORDER BY u.periodo_academico, m.orden, u.codigo"
        );
        $consulta->execute([$periodo, $programa]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Las unidades que dicta un docente en un periodo, con cuántos matriculados tienen.
     *
     * @return list<array<string, mixed>>
     */
    public function unidadesDe(int $docente, int $periodo): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT u.id_unidad, u.codigo, u.nombre, u.creditos, p.nombre AS programa,
                    (SELECT COUNT(*) FROM matricula_unidades mu
                      WHERE mu.id_unidad = u.id_unidad AND mu.id_periodo = ud.id_periodo AND mu.estado <> 'RETIRADO') AS matriculados
               FROM unidades_docentes ud
               JOIN unidades_didacticas u ON u.id_unidad = ud.id_unidad
               JOIN modulos_formativos m ON m.id_modulo = u.id_modulo
               JOIN programas_estudio p ON p.id_programa = m.id_programa
              WHERE ud.id_docente = ? AND ud.id_periodo = ?
              ORDER BY p.nombre, u.codigo"
        );
        $consulta->execute([$docente, $periodo]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Los alumnos de una unidad en un periodo (sin los retirados), con su nota.
     *
     * @return list<array<string, mixed>>
     */
    public function matriculados(int $unidad, int $periodo): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT mu.id_matricula_unidad, mu.estado, mu.nota_final, mu.nota_recuperacion, a.alum_dni AS dni,
                    CONCAT_WS(' ', a.alum_apepat, a.alum_apemat, a.alum_nombre) AS alumno
               FROM matricula_unidades mu
               JOIN alumnos a ON a.Id_alumno = mu.id_alumno
              WHERE mu.id_unidad = ? AND mu.id_periodo = ? AND mu.estado <> 'RETIRADO'
              ORDER BY a.alum_apepat, a.alum_apemat, a.alum_nombre"
        );
        $consulta->execute([$unidad, $periodo]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dicta(int $docente, int $unidad, int $periodo): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM unidades_docentes WHERE id_docente = ? AND id_unidad = ? AND id_periodo = ?');
        $consulta->execute([$docente, $unidad, $periodo]);
        return $consulta->fetchColumn() !== false;
    }

    /** Si la matrícula (id_matricula_unidad) es de una unidad que el docente dicta en ese periodo. */
    public function dictaMatricula(int $docente, int $idMatriculaUnidad): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT 1 FROM matricula_unidades mu
               JOIN unidades_docentes ud ON ud.id_unidad = mu.id_unidad AND ud.id_periodo = mu.id_periodo
              WHERE mu.id_matricula_unidad = ? AND ud.id_docente = ?'
        );
        $consulta->execute([$idMatriculaUnidad, $docente]);
        return $consulta->fetchColumn() !== false;
    }
}
