<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Demo comercial (Fase 4B.6): una institución creada con datos de ejemplo para que el colegio pruebe el
 * sistema; al convertirla en cliente se reemplaza por una base limpia (tools/convertir_demo.php o el panel).
 */
final class InstitucionesDemo extends AbstractMigration
{
    public function change(): void
    {
        $this->table('tenants')
            ->addColumn('demo', 'boolean', ['default' => false, 'after' => 'estado'])
            ->update();
    }
}
