<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo asignaturas/horarios: defectos que encontró tests/E2E/flujos.mjs §14.
 *
 *  1. ELIMINAR_ASIGNATURA: con docente asignado, la clave foránea lanzaba un error que llegaba como
 *     500 y el aviso del panel nunca aparecía. Ahora responde 0 sin borrar; 1 si la eliminó.
 *  2. SP_REGISTRAR_HORARIO_AULA solo evitaba repetir el MISMO curso en la misma celda: dos cursos
 *     podían ocupar la misma hora y día de un aula. Ahora:
 *       2 = ese curso ya está en esa celda (como antes),
 *       3 = la celda ya tiene otro curso,
 *       4 = el docente del curso ya tiene clase ese día en un horario que se solapa (en cualquier aula
 *           del mismo año escolar).
 *  3. SP_ELIMINAR_HORARIO borraba los horarios del aula de TODOS los años escolares, aunque el panel
 *     lista y elimina el del año en curso. Ahora recibe también el año.
 */
final class CorregirHorarios extends AbstractMigration
{
    use RecreaProcedimientos;

    public function up(): void
    {
        $this->recrear('ELIMINAR_ASIGNATURA', self::ELIMINAR_ASIGNATURA);
        $this->recrear('SP_REGISTRAR_HORARIO_AULA', self::REGISTRAR_HORARIO);
        $this->recrear('SP_ELIMINAR_HORARIO', self::ELIMINAR_HORARIO);
    }

    public function down(): void
    {
        foreach (['ELIMINAR_ASIGNATURA', 'SP_REGISTRAR_HORARIO_AULA', 'SP_ELIMINAR_HORARIO'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private const ELIMINAR_ASIGNATURA = <<<'SQL'
CREATE PROCEDURE `ELIMINAR_ASIGNATURA`(IN `ID` INT)
BEGIN
    IF EXISTS (SELECT 1 FROM detalle_asignatura_docente WHERE detalle_asignatura_docente.Id_asignatura = ID) THEN
        SELECT 0;
    ELSE
        DELETE FROM asignaturas WHERE Id_asignatura = ID;
        SELECT IF(ROW_COUNT() > 0, 1, 0);
    END IF;
END
SQL;

    private const REGISTRAR_HORARIO = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_HORARIO_AULA`(IN `HORA` INT, IN `CURSO` INT, IN `DIA` VARCHAR(20))
BEGIN
    DECLARE v_inicio TIME;
    DECLARE v_fin TIME;
    DECLARE v_anio INT;
    DECLARE v_docente INT;

    SELECT horas_aula.hora_inicio, horas_aula.hora_fin, horas_aula.`id_año_academico`
      INTO v_inicio, v_fin, v_anio
      FROM horas_aula WHERE horas_aula.id_hora = HORA;
    SELECT asignatura_docente.Id_docente INTO v_docente
      FROM detalle_asignatura_docente
      INNER JOIN asignatura_docente ON asignatura_docente.Id_asigdocente = detalle_asignatura_docente.Id_asig_docente
      WHERE detalle_asignatura_docente.Id_detalle_asig_docente = CURSO;

    IF EXISTS (SELECT 1 FROM horarios WHERE horarios.id_hora_aula = HORA AND horarios.id_detalle_asig_docente = CURSO AND horarios.dia = DIA) THEN
        SELECT 2;
    ELSEIF EXISTS (SELECT 1 FROM horarios WHERE horarios.id_hora_aula = HORA AND horarios.dia = DIA) THEN
        SELECT 3;
    ELSEIF v_docente IS NOT NULL AND EXISTS (
        SELECT 1 FROM horarios
        INNER JOIN horas_aula ON horas_aula.id_hora = horarios.id_hora_aula
        INNER JOIN detalle_asignatura_docente ON detalle_asignatura_docente.Id_detalle_asig_docente = horarios.id_detalle_asig_docente
        INNER JOIN asignatura_docente ON asignatura_docente.Id_asigdocente = detalle_asignatura_docente.Id_asig_docente
        WHERE horarios.dia = DIA AND asignatura_docente.Id_docente = v_docente
          AND horas_aula.`id_año_academico` = v_anio
          AND horas_aula.hora_inicio < v_fin AND v_inicio < horas_aula.hora_fin) THEN
        SELECT 4;
    ELSE
        INSERT INTO horarios (id_hora_aula, id_detalle_asig_docente, dia, estado, created_at, updated_at)
        VALUES (HORA, CURSO, DIA, 'ACTIVO', NOW(), NULL);
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR_HORARIO = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_HORARIO`(IN `ID` INT, IN `ANIO` INT)
BEGIN
    -- Solo el horario del aula en ESE año escolar (antes, el de todos los años).
    DELETE FROM horarios
    WHERE id_hora_aula IN (SELECT id_hora FROM horas_aula WHERE id_aula = ID AND `id_año_academico` = ANIO);
    SELECT 1;
END
SQL;
}
