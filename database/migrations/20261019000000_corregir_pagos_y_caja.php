<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo pensiones/pagos/ingresos/egresos: defectos que encontró tests/E2E/flujos.mjs §15.
 *
 *  1. Ingresos y egresos diversos: se registraban a nombre del «usu» del formulario y al editarlos se
 *     reescribía quién cobró/pagó. Ahora el responsable sale de la sesión (controlador) y editar no lo
 *     cambia (USU se conserva en la firma, ignorado). Solo se edita lo VALIDO, y un ingreso de un pago
 *     de pensión no se edita desde aquí (se descuadraría de su pago: se edita el pago).
 *  2. Un ingreso podía llevar un indicador de GASTOS y un egreso uno de INGRESOS: ahora 0.
 *  3. SP_REGISTRAR_PENSIONES buscaba el duplicado con «AND pensiones.fecha_vencimiento» (siempre
 *     verdadero): una sola pensión de cada mes por nivel PARA SIEMPRE; la del año siguiente respondía
 *     «ya existe». Ahora la regla es (nivel, mes, año del vencimiento), también al modificar.
 *  4. Eliminar una pensión con pagos o un indicador en uso daba 500 (clave foránea): ahora 0.
 *  5. Editar el monto de un pago no tocaba su ingreso: ahora el ingreso VALIDO lo sigue.
 *  6. «Anular pago» BORRABA el ingreso cobrado. Ahora lo anula (motivo, fecha y quién anula, que llega
 *     como parámetro nuevo desde la sesión), lo desliga del pago y elimina el pago, para que la pensión
 *     pueda cobrarse de nuevo. El ingreso anulado sigue en la caja del día.
 */
final class CorregirPagosYCaja extends AbstractMigration
{
    use RecreaProcedimientos;

    private const PROCEDIMIENTOS = [
        'SP_REGISTRAR_INGRESOS', 'SP_REGISTRAR_EGRESOS', 'SP_MODIFICAR_INGRESOS', 'SP_MODIFICAR_EGRESOS',
        'SP_REGISTRAR_PENSIONES', 'SP_MODIFICAR_PENSIONES', 'SP_ELIMINAR_PENSION',
        'SP_MODIFICAR_PAGO_PENSION', 'SP_ELIMINAR_PAGO_PENSION', 'SP_ELIMINAR_INDICADOR',
    ];

    public function up(): void
    {
        $this->recrear('SP_REGISTRAR_INGRESOS', self::registrar('ingresos', 'INGRESOS', 'id_pago_pension, '));
        $this->recrear('SP_REGISTRAR_EGRESOS', self::registrar('egresos', 'GASTOS', ''));
        $this->recrear('SP_MODIFICAR_INGRESOS', self::modificar('ingresos', 'id_ingreso', 'INGRESOS', ' AND ingresos.id_pago_pension IS NULL'));
        $this->recrear('SP_MODIFICAR_EGRESOS', self::modificar('egresos', 'id_egresos', 'GASTOS', ''));
        $this->recrear('SP_REGISTRAR_PENSIONES', self::REGISTRAR_PENSIONES);
        $this->recrear('SP_MODIFICAR_PENSIONES', self::MODIFICAR_PENSIONES);
        $this->recrear('SP_ELIMINAR_PENSION', self::ELIMINAR_PENSION);
        $this->recrear('SP_MODIFICAR_PAGO_PENSION', self::MODIFICAR_PAGO);
        $this->recrear('SP_ELIMINAR_PAGO_PENSION', self::ANULAR_PAGO);
        $this->recrear('SP_ELIMINAR_INDICADOR', self::ELIMINAR_INDICADOR);
    }

    public function down(): void
    {
        foreach (self::PROCEDIMIENTOS as $nombre) {
            $sql = esquema_procedimiento_original($nombre);
            // Los montos de la firma original son DECIMAL(5,2); la migración 20261015000000 (anterior a
            // esta) los dejó en DECIMAL(10,2): se respeta ese estado.
            $this->recrear($nombre, (string) preg_replace('/decimal\s*\(\s*5\s*,\s*2\s*\)/i', 'DECIMAL(10,2)', $sql));
        }
    }

    private static function registrar(string $tabla, string $tipo, string $columnaPago): string
    {
        $procedimiento = $tabla === 'ingresos' ? 'SP_REGISTRAR_INGRESOS' : 'SP_REGISTRAR_EGRESOS';
        $valorPago = $columnaPago === '' ? '' : 'NULL, ';
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(10,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM indicadores WHERE id_indicadores = INDI AND tipo_indicador = '$tipo') THEN
        SELECT 0;
    ELSE
        INSERT INTO $tabla ({$columnaPago}id_indicador, id_user, cantidad, monto, observacion, estado, created_at, updated)
        VALUES ({$valorPago}INDI, USU, CANTIDAD, MONTO, OBSERVA, 'VALIDO', CURDATE(), NULL);
        SELECT 1;
    END IF;
END
SQL;
    }

    private static function modificar(string $tabla, string $clave, string $tipo, string $soloDiversos): string
    {
        $procedimiento = $tabla === 'ingresos' ? 'SP_MODIFICAR_INGRESOS' : 'SP_MODIFICAR_EGRESOS';
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `ID` INT, IN `INDI` INT, IN `CANTIDAD` INT, IN `MONTO` DECIMAL(10,2), IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
    -- USU se ignora: editar no cambia quién cobró/pagó (id_user).
    IF NOT EXISTS (SELECT 1 FROM indicadores WHERE id_indicadores = INDI AND tipo_indicador = '$tipo')
       OR NOT EXISTS (SELECT 1 FROM $tabla WHERE $tabla.$clave = ID AND $tabla.estado = 'VALIDO'$soloDiversos) THEN
        SELECT 0;
    ELSE
        UPDATE $tabla SET id_indicador = INDI, cantidad = CANTIDAD, monto = MONTO, observacion = OBSERVA, updated = NOW()
        WHERE $tabla.$clave = ID;
        SELECT 1;
    END IF;
END
SQL;
    }

    private const REGISTRAR_PENSIONES = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_PENSIONES`(IN `NIVEL` INT, IN `MES` VARCHAR(30), IN `FECHA_VEN` DATE, IN `PRECIO` DECIMAL(10,2), IN `MORA` DECIMAL(10,2))
BEGIN
    IF EXISTS (SELECT 1 FROM pensiones WHERE id_nivel_academico = NIVEL AND mes = MES AND YEAR(fecha_vencimiento) = YEAR(FECHA_VEN)) THEN
        SELECT 2;
    ELSE
        INSERT INTO pensiones (id_nivel_academico, mes, fecha_vencimiento, precio, mora, created_at, updated_at)
        VALUES (NIVEL, MES, FECHA_VEN, PRECIO, MORA, NOW(), NULL);
        SELECT 1;
    END IF;
END
SQL;

    private const MODIFICAR_PENSIONES = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_PENSIONES`(IN `ID` INT, IN `NIVEL` INT, IN `MES` VARCHAR(30), IN `FECHA_VEN` DATE, IN `PRECIO` DECIMAL(10,2), IN `MORA` DECIMAL(10,2))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pensiones WHERE id_pensiones = ID) THEN
        SELECT 0;
    ELSEIF EXISTS (SELECT 1 FROM pensiones WHERE id_nivel_academico = NIVEL AND mes = MES
                   AND YEAR(fecha_vencimiento) = YEAR(FECHA_VEN) AND id_pensiones <> ID) THEN
        SELECT 2;
    ELSE
        UPDATE pensiones SET id_nivel_academico = NIVEL, mes = MES, fecha_vencimiento = FECHA_VEN,
            precio = PRECIO, mora = MORA, updated_at = NOW()
        WHERE id_pensiones = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR_PENSION = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_PENSION`(IN `ID` INT)
BEGIN
    IF EXISTS (SELECT 1 FROM pago_pensiones WHERE id_pension = ID) THEN
        SELECT 0;
    ELSE
        DELETE FROM pensiones WHERE id_pensiones = ID;
        SELECT IF(ROW_COUNT() > 0, 1, 0);
    END IF;
END
SQL;

    private const MODIFICAR_PAGO = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_PAGO_PENSION`(IN `ID` INT, IN `MONTO` DECIMAL(10,2), IN `DESCRIP` VARCHAR(255))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pago_pensiones WHERE id_pago_pension = ID) THEN
        SELECT 0;
    ELSE
        UPDATE pago_pensiones SET sub_total = MONTO, motivo_edicion = DESCRIP, updated_at = NOW()
        WHERE id_pago_pension = ID;
        -- El ingreso del pago sigue al monto (un ingreso anulado conserva lo que se anuló).
        UPDATE ingresos SET monto = MONTO, updated = NOW() WHERE id_pago_pension = ID AND estado = 'VALIDO';
        SELECT 1;
    END IF;
END
SQL;

    private const ANULAR_PAGO = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_PAGO_PENSION`(IN `ID` INT, IN `USU` INT)
BEGIN
    DECLARE v_motivo VARCHAR(255);
    SELECT CONCAT('PAGO ANULADO: ', concepto, ' (MATRÍCULA ', id_matri, IFNULL(CONCAT(', PENSIÓN ', id_pension), ''), ')')
      INTO v_motivo FROM pago_pensiones WHERE id_pago_pension = ID;

    IF v_motivo IS NULL THEN
        SELECT 0;
    ELSE
        -- El ingreso no se borra: se anula, se desliga del pago y queda en la caja.
        UPDATE ingresos SET estado = 'ANULADO', motivo_anulacion = v_motivo, fecha_anulacion = CURDATE(),
            id_usuario_anulacion = USU
        WHERE id_pago_pension = ID AND estado = 'VALIDO';
        UPDATE ingresos SET id_pago_pension = NULL WHERE id_pago_pension = ID;
        DELETE FROM pago_pensiones WHERE id_pago_pension = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR_INDICADOR = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_INDICADOR`(IN `ID` INT)
BEGIN
    IF EXISTS (SELECT 1 FROM ingresos WHERE id_indicador = ID) OR EXISTS (SELECT 1 FROM egresos WHERE id_indicador = ID) THEN
        SELECT 0;
    ELSE
        DELETE FROM indicadores WHERE id_indicadores = ID;
        SELECT IF(ROW_COUNT() > 0, 1, 0);
    END IF;
END
SQL;
}
