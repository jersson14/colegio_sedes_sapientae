<?php

declare(strict_types=1);

/**
 * Phinx de la BD maestra (Fase 4, modo múltiple): registro de instituciones.
 *   vendor/bin/phinx migrate -c phinx_maestro.php
 *
 * Misma lectura de credenciales que phinx.php; la base es MAESTRO_DB_NAME (por defecto sge_maestro).
 */
$base = require __DIR__ . '/phinx.php';
$principal = $base['environments']['principal'];
$nombre = getenv('MAESTRO_DB_NAME');
if ($nombre === false || $nombre === '') {
    $nombre = 'sge_maestro';
    // Mismo criterio que phinx.php: con DB_NAME en el entorno (CI) no se lee colegio.env.
    if (getenv('DB_NAME') === false) {
        require_once __DIR__ . '/core/config.php';
        $nombre = (string) config('MAESTRO_DB_NAME', 'sge_maestro');
    }
}

return [
    'paths' => ['migrations' => __DIR__ . '/database/maestro'],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'maestro',
        'maestro' => ['name' => $nombre] + $principal,
    ],
    'version_order' => 'creation',
];
