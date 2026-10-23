<?php
/**
 * Configuración general del sistema.
 * Los valores de cada servidor (credenciales, producción) van en config.local.php,
 * que no se versiona. Ver config.local.php.example.
 */
$config = [
    'db' => [
        'host'       => 'localhost',
        'puerto'     => 3307,
        'usuario'    => 'root',
        'contrasena' => '',
        'nombre'     => 'colegio2',
    ],
    'zona_horaria' => 'America/Lima',
    // En producción: true (oculta errores y exige cookie segura si hay HTTPS)
    'produccion'   => false,
    // Intentos fallidos de login antes de bloquear y minutos de bloqueo
    'login_max_intentos'    => 5,
    'login_minutos_bloqueo' => 15,
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}

return $config;
