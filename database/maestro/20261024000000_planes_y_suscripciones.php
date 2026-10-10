<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Empaquetado comercial (Fase 4B, hito 4B.1): planes, suscripciones y facturas en la BD maestra.
 *
 * Los precios y límites son DATOS (se editan en el panel de superadministrador), no código: la estructura
 * admite tarifa plana (precio_mensual), por alumno (precio_por_alumno) o ambas. Un límite NULL = sin límite.
 * Solo se siembra el plan PRUEBA (sin precio), para que un colegio nuevo tenga límites desde el principio.
 */
final class PlanesYSuscripciones extends AbstractMigration
{
    public function up(): void
    {
        $this->table('planes', ['signed' => false])
            ->addColumn('codigo', 'string', ['limit' => 30])
            ->addColumn('nombre', 'string', ['limit' => 100])
            ->addColumn('max_alumnos', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('max_usuarios', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('max_almacenamiento_mb', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('precio_mensual', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('precio_por_alumno', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('moneda', 'char', ['limit' => 3, 'default' => 'PEN'])
            ->addColumn('activo', 'boolean', ['default' => true])
            ->addColumn('creado', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['codigo'], ['unique' => true])
            ->create();

        $this->table('suscripciones', ['signed' => false])
            ->addColumn('tenant_id', 'integer', ['signed' => false])
            ->addColumn('plan_id', 'integer', ['signed' => false])
            ->addColumn('inicio', 'date')
            ->addColumn('fin', 'date', ['null' => true])
            ->addColumn('ciclo', 'enum', ['values' => ['MENSUAL', 'ANUAL'], 'default' => 'MENSUAL'])
            ->addColumn('vigente', 'boolean', ['default' => true])
            ->addColumn('creado', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'RESTRICT'])
            ->addForeignKey('plan_id', 'planes', 'id', ['delete' => 'RESTRICT'])
            ->addIndex(['tenant_id', 'vigente'])
            ->create();

        $this->table('facturas', ['signed' => false])
            ->addColumn('tenant_id', 'integer', ['signed' => false])
            ->addColumn('suscripcion_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('numero', 'string', ['limit' => 20])
            ->addColumn('periodo_inicio', 'date')
            ->addColumn('periodo_fin', 'date')
            ->addColumn('emision', 'date')
            ->addColumn('vencimiento', 'date')
            ->addColumn('monto', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('moneda', 'char', ['limit' => 3, 'default' => 'PEN'])
            ->addColumn('detalle', 'string', ['limit' => 255, 'default' => ''])
            ->addColumn('estado', 'enum', ['values' => ['PENDIENTE', 'PAGADA', 'ANULADA'], 'default' => 'PENDIENTE'])
            ->addColumn('pagada_en', 'datetime', ['null' => true])
            ->addColumn('medio_pago', 'string', ['limit' => 40, 'default' => ''])
            ->addColumn('referencia_pago', 'string', ['limit' => 100, 'default' => ''])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'RESTRICT'])
            ->addForeignKey('suscripcion_id', 'suscripciones', 'id', ['delete' => 'SET_NULL'])
            ->addIndex(['numero'], ['unique' => true])
            ->addIndex(['tenant_id', 'periodo_inicio'], ['unique' => true])
            ->addIndex(['estado', 'vencimiento'])
            ->create();

        // Cuándo pasó a SUSPENDIDO (abre la ventana de exportación) y hasta cuándo dura la prueba.
        $this->table('tenants')
            ->addColumn('prueba_hasta', 'date', ['null' => true, 'after' => 'estado'])
            ->addColumn('suspendido_desde', 'datetime', ['null' => true, 'after' => 'prueba_hasta'])
            ->addColumn('cancelado_en', 'datetime', ['null' => true, 'after' => 'suspendido_desde'])
            ->update();

        $this->execute("INSERT INTO planes (codigo, nombre, max_alumnos, max_usuarios, max_almacenamiento_mb)
            VALUES ('PRUEBA', 'Prueba', 50, 15, 200)");
    }

    public function down(): void
    {
        $this->table('tenants')->removeColumn('cancelado_en')->removeColumn('suspendido_desde')->removeColumn('prueba_hasta')->update();
        $this->table('facturas')->drop()->save();
        $this->table('suscripciones')->drop()->save();
        $this->table('planes')->drop()->save();
    }
}
