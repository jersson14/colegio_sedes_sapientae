<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Baja de instituciones (Fase 4B.8): hasta cuándo se conservan sus datos tras cancelar (retención
 * pactada) y cuándo se borraron de verdad (tools/baja_tenant.php purgar).
 */
final class BajaDeInstituciones extends AbstractMigration
{
    public function change(): void
    {
        $this->table('tenants')
            ->addColumn('retencion_hasta', 'date', ['null' => true, 'after' => 'cancelado_en'])
            ->addColumn('borrado_en', 'datetime', ['null' => true, 'after' => 'retencion_hasta'])
            ->update();
    }
}
