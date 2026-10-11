<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Módulos opcionales por institución, que activa el superadministrador (App\Comercial\ModuloOpcional):
 * facturación electrónica con Nubefact y asistente de IA.
 *
 * - tenant_modulos: qué módulos tiene cada institución y, si aplica, su límite mensual (consultas de IA).
 * - consumo_ia: consultas y tokens de IA por institución y mes; la clave de la API es de la plataforma,
 *   así que este consumo es lo que se cobra o se limita.
 * En modo único no hay BD maestra: los módulos salen de MODULOS_ACTIVOS en colegio.env.
 */
final class ModulosOpcionales extends AbstractMigration
{
    public function up(): void
    {
        $this->table('tenant_modulos', ['id' => false, 'primary_key' => ['tenant_id', 'modulo']])
            ->addColumn('tenant_id', 'integer', ['signed' => false])
            ->addColumn('modulo', 'string', ['limit' => 40])
            ->addColumn('activo', 'boolean', ['default' => false])
            ->addColumn('limite_mensual', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('actualizado', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('actualizado_por', 'string', ['limit' => 100, 'default' => ''])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('consumo_ia', ['id' => false, 'primary_key' => ['tenant_id', 'mes']])
            ->addColumn('tenant_id', 'integer', ['signed' => false])
            ->addColumn('mes', 'char', ['limit' => 7])
            ->addColumn('consultas', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('tokens_entrada', 'biginteger', ['signed' => false, 'default' => 0])
            ->addColumn('tokens_salida', 'biginteger', ['signed' => false, 'default' => 0])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('consumo_ia')->drop()->save();
        $this->table('tenant_modulos')->drop()->save();
    }
}
