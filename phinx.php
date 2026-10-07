<?php

declare(strict_types=1);

/**
 * Phinx (Fase 1): todo cambio de esquema va en database/migrations/.
 *
 * Credenciales, en este orden:
 *   1. Variables de entorno DB_* (CI): DB_HOST, DB_PORT, DB_NAME, DB_MIGRACION_USER, DB_MIGRACION_PASS
 *   2. colegio.env (local), vía core/config.php
 * Las migraciones necesitan DDL: usan DB_MIGRACION_USER, nunca el usuario de la app
 * (colegio_app solo tiene EXECUTE/SELECT). Si falta, se usa DB_USER.
 */
$leer = static function (string $clave, string $porDefecto = ''): string {
    $env = getenv($clave);
    if ($env !== false && $env !== '') {
        return $env;
    }
    if (getenv('DB_NAME') === false) {
        require_once __DIR__ . '/core/config.php';
        return (string) config($clave, $porDefecto);
    }
    return $porDefecto;
};

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds'      => __DIR__ . '/database/seeders',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'principal',
        'principal' => [
            'adapter' => 'mysql',
            'host'    => $leer('DB_HOST', 'localhost'),
            'port'    => (int) $leer('DB_PORT', '3306'),
            'name'    => $leer('DB_NAME', 'colegio'),
            'user'    => $leer('DB_MIGRACION_USER') ?: $leer('DB_USER'),
            'pass'    => $leer('DB_MIGRACION_USER') !== '' ? $leer('DB_MIGRACION_PASS') : $leer('DB_PASS'),
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
