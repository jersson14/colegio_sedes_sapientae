<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo tareas/exámenes: defectos que encontró tests/E2E/flujos.mjs §16.
 *
 *  1. SP_ENVIAR_TAREA / SP_MODIFICAR_ENVIAR_TAREA aceptaban entregas de tareas vencidas o finalizadas
 *     y reemplazar una entrega ya CALIFICADA (volvía a «ENVIADO»). Ahora 0 en esos casos; 1 si se
 *     guardó (antes no respondían nada).
 *  2. SP_MODIFICAR_TAREA_ESTATUS aceptaba cualquier estado y, con cualquiera, calificaba con 5 «NO
 *     ENTREGÓ» a los pendientes (el panel solo finaliza). Ahora solo FINALIZADO; si no, 0.
 *  3. SP_ELIMINAR_TAREA borraba en cascada las entregas enviadas y calificadas: ahora 0 si las hay.
 *  4. SP_REGISTRAR_CALIFICACION respondía éxito aunque la entrega no existiera: ahora 1/0.
 *  5. SP_MODIFICAR_EXAMENES guardaba la fecha actual en una variable DATE y la comparaba con el
 *     DATETIME recibido: editar un examen con hora sin cambiar la fecha respondía «ya existe».
 *     Ahora la regla es (curso, fecha) sin contar el propio examen.
 *  6. SP_REGISTRAR_EXAMENES daba al primer examen el código «D0000001» en lugar de «E0000001».
 *  7. SP_MODIFICAR_EXAMEN_ESTATUS: el estado se valida en App\Domain\Tarea; ahora responde 1/0.
 */
final class CorregirTareasYExamenes extends AbstractMigration
{
    use RecreaProcedimientos;

    private const PROCEDIMIENTOS = [
        'SP_ENVIAR_TAREA', 'SP_MODIFICAR_ENVIAR_TAREA', 'SP_MODIFICAR_TAREA_ESTATUS', 'SP_ELIMINAR_TAREA',
        'SP_REGISTRAR_CALIFICACION', 'SP_MODIFICAR_EXAMENES', 'SP_REGISTRAR_EXAMENES', 'SP_MODIFICAR_EXAMEN_ESTATUS',
    ];

    public function up(): void
    {
        $this->recrear('SP_ENVIAR_TAREA', self::entregar('SP_ENVIAR_TAREA', "estado = 'ENVIADO', fecha_envio = NOW()"));
        $this->recrear('SP_MODIFICAR_ENVIAR_TAREA', self::entregar('SP_MODIFICAR_ENVIAR_TAREA', 'updated_at = NOW()'));
        $this->recrear('SP_MODIFICAR_TAREA_ESTATUS', self::ESTATUS_TAREA);
        $this->recrear('SP_ELIMINAR_TAREA', self::ELIMINAR_TAREA);
        $this->recrear('SP_REGISTRAR_CALIFICACION', self::CALIFICAR);
        $this->recrear('SP_MODIFICAR_EXAMENES', self::MODIFICAR_EXAMEN);
        $this->recrear('SP_REGISTRAR_EXAMENES', self::reemplazarUnaVez(
            esquema_procedimiento_original('SP_REGISTRAR_EXAMENES'),
            "SET @cod :=(SELECT CONCAT('D0000001'));",
            "SET @cod :=(SELECT CONCAT('E0000001'));"
        ));
        $this->recrear('SP_MODIFICAR_EXAMEN_ESTATUS', self::ESTATUS_EXAMEN);
    }

    public function down(): void
    {
        foreach (self::PROCEDIMIENTOS as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    /** Entrega del alumno: solo en plazo, con la tarea pendiente y sin calificar. */
    private static function entregar(string $procedimiento, string $marca): string
    {
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `ID` CHAR(12), IN `RUTA` VARCHAR(255))
BEGIN
    IF EXISTS (SELECT 1 FROM detalle_tarea INNER JOIN tareas ON tareas.id_tarea = detalle_tarea.id_tarea
               WHERE detalle_tarea.id_detalle_tarea = ID AND detalle_tarea.estado <> 'CALIFICADO'
                 AND tareas.estado = 'PENDIENTE' AND tareas.fecha_entrega > NOW()) THEN
        UPDATE detalle_tarea SET archivo_evnio_tarea = RUTA, $marca WHERE id_detalle_tarea = ID;
        SELECT 1;
    ELSE
        SELECT 0;
    END IF;
END
SQL;
    }

    private const ESTATUS_TAREA = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_TAREA_ESTATUS`(IN `ID` CHAR(12), IN `ESTATU` VARCHAR(20))
BEGIN
    -- Solo se finaliza; al finalizar, quien no entregó queda calificado con 5 (regla del colegio).
    IF ESTATU <> 'FINALIZADO' OR NOT EXISTS (SELECT 1 FROM tareas WHERE id_tarea = ID) THEN
        SELECT 0;
    ELSE
        UPDATE tareas SET estado = ESTATU, updated_at = NOW() WHERE tareas.id_tarea = ID;
        UPDATE detalle_tarea SET calificacion = 5, observacion = 'NO ENTREGO TAREA', estado = 'CALIFICADO', updated_at = NOW()
        WHERE detalle_tarea.id_tarea = ID AND detalle_tarea.estado = 'PENDIENTE';
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR_TAREA = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_TAREA`(IN `ID` CHAR(12))
BEGIN
    IF EXISTS (SELECT 1 FROM detalle_tarea WHERE id_tarea = ID AND estado <> 'PENDIENTE') THEN
        SELECT 0;
    ELSE
        DELETE FROM tareas WHERE id_tarea = ID;
        SELECT IF(ROW_COUNT() > 0, 1, 0);
    END IF;
END
SQL;

    private const CALIFICAR = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_CALIFICACION`(IN `ID` INT, IN `NOTA` INT, IN `OBSER` VARCHAR(255))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM detalle_tarea WHERE id_detalle_tarea = ID) THEN
        SELECT 0;
    ELSE
        UPDATE detalle_tarea SET calificacion = NOTA, observacion = OBSER, estado = 'CALIFICADO', updated_at = NOW()
        WHERE detalle_tarea.id_detalle_tarea = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const MODIFICAR_EXAMEN = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_EXAMENES`(IN `ID` CHAR(12), IN `IDDETA` INT, IN `TEMA` VARCHAR(255), IN `FECHA` DATETIME, IN `DESCRIP` VARCHAR(255))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM examen WHERE id_examen = ID) THEN
        SELECT 0;
    ELSEIF EXISTS (SELECT 1 FROM examen WHERE id_detalle_asignatura = IDDETA AND fecha_examen = FECHA AND id_examen <> ID) THEN
        SELECT 2;
    ELSE
        UPDATE examen SET id_detalle_asignatura = IDDETA, tema_examen = TEMA, descripcion = DESCRIP,
            fecha_examen = FECHA, updated_at = NOW()
        WHERE id_examen = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ESTATUS_EXAMEN = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_EXAMEN_ESTATUS`(IN `ID` CHAR(12), IN `ESTA` VARCHAR(20))
BEGIN
    IF ESTA NOT IN ('PENDIENTE', 'REALIZADO') OR NOT EXISTS (SELECT 1 FROM examen WHERE id_examen = ID) THEN
        SELECT 0;
    ELSE
        UPDATE examen SET estado = ESTA, updated_at = NOW() WHERE examen.id_examen = ID;
        SELECT 1;
    END IF;
END
SQL;
}
