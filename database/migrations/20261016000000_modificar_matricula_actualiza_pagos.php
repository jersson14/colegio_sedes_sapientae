<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Editar los montos de una matrícula ahora actualiza también sus pagos ya registrados (decisión del
 * responsable). Antes, SP_MODIFICAR_MATRICULA cambiaba matricula.pago_* pero los pagos de ADMISION,
 * ALUMNO NUEVO y MATRICULA y sus ingresos seguían con el monto anterior: la ficha y la caja no
 * cuadraban.
 *
 * Se actualizan el pago de cada concepto y su ingreso si está VALIDO; un ingreso ANULADO conserva su
 * monto (es el registro de lo que se anuló). Pensiones y otros conceptos no se tocan.
 */
final class ModificarMatriculaActualizaPagos extends AbstractMigration
{
    use RecreaProcedimientos;

    private const ANCLA = "        SELECT 1;\n    END IF;\nEND";

    private const PAGOS = <<<'SQL'
        -- Migración 20261016000000: los pagos de la matrícula y sus ingresos válidos siguen a los montos.
        UPDATE pago_pensiones SET sub_total = ADMIN, updated_at = NOW()
            WHERE id_matri = ID AND concepto = 'ADMISION';
        UPDATE pago_pensiones SET sub_total = NUEVO, updated_at = NOW()
            WHERE id_matri = ID AND concepto = 'ALUMNO NUEVO';
        UPDATE pago_pensiones SET sub_total = MATRI, updated_at = NOW()
            WHERE id_matri = ID AND concepto = 'MATRICULA';
        UPDATE ingresos INNER JOIN pago_pensiones ON pago_pensiones.id_pago_pension = ingresos.id_pago_pension
            SET ingresos.monto = pago_pensiones.sub_total
            WHERE pago_pensiones.id_matri = ID AND ingresos.estado = 'VALIDO'
              AND pago_pensiones.concepto IN ('ADMISION', 'ALUMNO NUEVO', 'MATRICULA');

SQL;

    public function up(): void
    {
        $sql = self::reemplazarUnaVez($this->definicionActual('SP_MODIFICAR_MATRICULA'), self::ANCLA, self::PAGOS . self::ANCLA);
        $this->recrear('SP_MODIFICAR_MATRICULA', $sql);
    }

    public function down(): void
    {
        $sql = self::reemplazarUnaVez($this->definicionActual('SP_MODIFICAR_MATRICULA'), self::PAGOS . self::ANCLA, self::ANCLA);
        $this->recrear('SP_MODIFICAR_MATRICULA', $sql);
    }
}
