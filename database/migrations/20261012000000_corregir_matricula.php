<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo matrícula: defectos que encontró tests/E2E/flujos.mjs §10.
 *
 *  1. SP_REGISTRAR_MATRICULA creaba la cuenta de un alumno NUEVO aunque el usuario ya existiera
 *     (dos cuentas con el mismo nombre). Ahora responde 3 sin tocar nada. Un alumno inexistente
 *     responde 0 (antes no devolvía nada).
 *  2. SP_MODIFICAR_MATRICULA solo impedía repetir (alumno, aula): se podía mover una matrícula a un
 *     año donde el alumno ya estaba matriculado. Ahora la regla es la del registro: (alumno, año).
 *     Y ya no cambia el alumno de la matrícula (la cuenta usu_id seguía siendo la del anterior):
 *     IDESTU se conserva en la firma pero se ignora.
 *  3. SP_ELIMINAR_MATRICULA borraba si había 3 pagos o menos, y la cascada se llevaba los INGRESOS
 *     cobrados, las notas, las asistencias y las tareas entregadas. Ahora responde 2 si hay pensiones,
 *     ingresos válidos con monto, notas, asistencias, tareas entregadas o atenciones (los ingresos se
 *     anulan antes, con la anulación que ya existe). Si era la última matrícula del alumno, este
 *     vuelve a NUEVO y se borra su cuenta si nada más la usa, para poder volver a matricularlo.
 *
 * Los montos (DECIMAL(5,2): hasta 999.99) los valida App\Domain\Matricula; ampliar las columnas es
 * una decisión aparte (afecta a pagos e ingresos).
 */
final class CorregirMatricula extends AbstractMigration
{
    use RecreaProcedimientos;

    private const ETIQUETA = ['from' => "\nBEGIN\n", 'to' => "\nregistro: BEGIN\n"];
    private const ANCLA_VALIDACION = "\n    IF (TIPO = 'NUEVO' OR TIPO = 'ANTIGUO') AND CANTIDAD = 0 THEN\n";

    private const VALIDACION = <<<'SQL'

    -- Migración 20261012000000: alumno inexistente → 0; usuario de un alumno NUEVO ya ocupado → 3.
    IF TIPO IS NULL THEN
        SELECT 0;
        LEAVE registro;
    END IF;
    IF TIPO = 'NUEVO' AND CANTIDAD = 0 AND EXISTS (SELECT 1 FROM usuario WHERE usuario.usu_usuario = USU) THEN
        SELECT 3;
        LEAVE registro;
    END IF;
SQL;

    public function up(): void
    {
        $registrar = $this->definicionActual('SP_REGISTRAR_MATRICULA');
        $registrar = self::reemplazarUnaVez($registrar, self::ETIQUETA['from'], self::ETIQUETA['to']);
        $registrar = self::reemplazarUnaVez($registrar, self::ANCLA_VALIDACION, self::VALIDACION . self::ANCLA_VALIDACION);
        $this->recrear('SP_REGISTRAR_MATRICULA', $registrar);
        $this->recrear('SP_MODIFICAR_MATRICULA', self::MODIFICAR);
        $this->recrear('SP_ELIMINAR_MATRICULA', self::ELIMINAR);
    }

    public function down(): void
    {
        $registrar = $this->definicionActual('SP_REGISTRAR_MATRICULA');
        $registrar = self::reemplazarUnaVez($registrar, self::VALIDACION . self::ANCLA_VALIDACION, self::ANCLA_VALIDACION);
        $registrar = self::reemplazarUnaVez($registrar, self::ETIQUETA['to'], self::ETIQUETA['from']);
        $this->recrear('SP_REGISTRAR_MATRICULA', $registrar);
        foreach (['SP_MODIFICAR_MATRICULA', 'SP_ELIMINAR_MATRICULA'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private const MODIFICAR = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_MATRICULA`(IN `ID` INT, IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT, IN `ADMIN` DECIMAL(5,2), IN `NUEVO` DECIMAL(5,2), IN `MATRI` DECIMAL(5,2), IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50))
BEGIN
    -- IDESTU se ignora: el alumno (y su cuenta) de una matrícula no cambian al editarla.
    DECLARE v_alumno INT;
    SELECT matricula.id_alumno INTO v_alumno FROM matricula WHERE matricula.id_matricula = ID;

    IF v_alumno IS NULL THEN
        SELECT 0;
    ELSEIF EXISTS (SELECT 1 FROM matricula WHERE matricula.id_alumno = v_alumno
                   AND matricula.`id_año` = `AÑO` AND matricula.id_matricula <> ID) THEN
        SELECT 2;
    ELSE
        UPDATE matricula SET
            `id_año` = `AÑO`,
            id_aula = AULA,
            pago_admi = ADMIN,
            pago_alu_nuevo = NUEVO,
            pago_matricula = MATRI,
            procedencia_colegio = PROCEDEN,
            provincia = PROVI,
            departamento = DEPAR,
            updated_at = NOW()
        WHERE id_matricula = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_MATRICULA`(IN `ID` INT)
BEGIN
    DECLARE v_alumno INT;
    DECLARE v_usuario INT;
    SELECT matricula.id_alumno, matricula.usu_id INTO v_alumno, v_usuario
    FROM matricula WHERE matricula.id_matricula = ID;

    IF v_alumno IS NULL THEN
        SELECT 0;
    ELSEIF EXISTS (SELECT 1 FROM pago_pensiones WHERE id_matri = ID AND concepto = 'PENSION')
        OR EXISTS (SELECT 1 FROM ingresos INNER JOIN pago_pensiones ON pago_pensiones.id_pago_pension = ingresos.id_pago_pension
                   WHERE pago_pensiones.id_matri = ID AND ingresos.estado = 'VALIDO' AND ingresos.monto > 0)
        OR EXISTS (SELECT 1 FROM notas WHERE id_matricula = ID)
        OR EXISTS (SELECT 1 FROM notas_padre WHERE id_matricula = ID)
        OR EXISTS (SELECT 1 FROM asistencia WHERE id_matricula = ID)
        OR EXISTS (SELECT 1 FROM detalle_tarea WHERE id_matriculado = ID)
        OR EXISTS (SELECT 1 FROM atencion_salud WHERE id_matricula = ID) THEN
        SELECT 2;
    ELSE
        DELETE FROM matricula WHERE id_matricula = ID;
        -- Sin otras matrículas, el alumno vuelve a NUEVO; su cuenta se borra si nada más la usa.
        IF NOT EXISTS (SELECT 1 FROM matricula WHERE id_alumno = v_alumno) THEN
            UPDATE alumnos SET tipo_alum = 'NUEVO', alum_estatus = 'NO', updated_at = NOW() WHERE Id_alumno = v_alumno;
            IF v_usuario IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM ingresos WHERE id_user = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM egresos WHERE id_user = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM comunicados WHERE id_usuario = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM atencion_salud WHERE id_usuario = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM docentes WHERE id_asusuario = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM personal_admi WHERE id_ausuario = v_usuario)
               AND NOT EXISTS (SELECT 1 FROM auxiliar WHERE id_usuario = v_usuario) THEN
                DELETE FROM usuario WHERE usu_id = v_usuario;
            END IF;
        END IF;
        SELECT 1;
    END IF;
END
SQL;
}
