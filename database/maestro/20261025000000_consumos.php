<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Medición de consumo por institución (Fase 4B.4): una foto diaria de lo que usa de su plan, que toma
 * tools/tareas_programadas.php. Sirve para el panel, para cobrar por alumno y para ver la evolución.
 */
final class Consumos extends AbstractMigration
{
    public function change(): void
    {
        $this->table('consumos', ['signed' => false])
            ->addColumn('tenant_id', 'integer', ['signed' => false])
            ->addColumn('fecha', 'date')
            ->addColumn('alumnos', 'integer', ['signed' => false])
            ->addColumn('usuarios', 'integer', ['signed' => false])
            ->addColumn('almacenamiento_mb', 'integer', ['signed' => false])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'RESTRICT'])
            ->addIndex(['tenant_id', 'fecha'], ['unique' => true])
            ->create();
    }
}
