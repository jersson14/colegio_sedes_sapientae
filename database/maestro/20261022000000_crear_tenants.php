<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * BD maestra (Fase 4, hito 4.1): el registro de instituciones del modo múltiple.
 * Se migra aparte de las bases de los colegios: vendor/bin/phinx migrate -c phinx_maestro.php
 */
final class CrearTenants extends AbstractMigration
{
    public function change(): void
    {
        $this->table('tenants', ['signed' => false])
            ->addColumn('slug', 'string', ['limit' => 63, 'comment' => 'Subdominio: <slug>.TENANT_DOMINIO'])
            ->addColumn('dominio', 'string', ['limit' => 253, 'null' => true, 'comment' => 'Dominio propio, opcional'])
            ->addColumn('razon_social', 'string', ['limit' => 200])
            ->addColumn('tipo', 'enum', ['values' => ['COLEGIO', 'INSTITUTO', 'CETPRO'], 'default' => 'COLEGIO'])
            ->addColumn('base_datos', 'string', ['limit' => 64])
            ->addColumn('estado', 'enum', [
                'values' => ['PRUEBA', 'ACTIVO', 'MOROSO', 'SUSPENDIDO', 'CANCELADO'],
                'default' => 'PRUEBA',
            ])
            ->addColumn('fecha_alta', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['dominio'], ['unique' => true])
            ->addIndex(['base_datos'], ['unique' => true])
            ->create();
    }
}
