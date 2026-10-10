<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Panel de superadministrador (Fase 4, hito 4.8): sus cuentas, separadas de las de los colegios, y el
 * registro de auditoría de lo que se hace desde el panel (quién, qué, sobre qué colegio, cuándo, desde dónde).
 */
final class SuperadminYAuditoria extends AbstractMigration
{
    public function change(): void
    {
        $this->table('superadmins', ['signed' => false])
            ->addColumn('usuario', 'string', ['limit' => 60])
            ->addColumn('nombre', 'string', ['limit' => 120])
            ->addColumn('clave_hash', 'string', ['limit' => 255])
            ->addColumn('activo', 'boolean', ['default' => true])
            ->addColumn('ultimo_acceso', 'datetime', ['null' => true])
            ->addColumn('creado', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['usuario'], ['unique' => true])
            ->create();

        $this->table('auditoria', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('fecha', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('actor', 'string', ['limit' => 60])
            ->addColumn('accion', 'string', ['limit' => 40])
            ->addColumn('tenant', 'string', ['limit' => 63, 'null' => true])
            ->addColumn('detalle', 'string', ['limit' => 500, 'default' => ''])
            ->addColumn('ip', 'string', ['limit' => 45, 'default' => ''])
            ->addIndex(['fecha'])
            ->addIndex(['tenant'])
            ->create();
    }
}
