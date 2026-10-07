<?php

declare(strict_types=1);

/**
 * Configuración desde un archivo .env FUERA del docroot (Fase 0, punto 0.4).
 *
 * Ubicación, en este orden:
 *   1. Variable de entorno COLEGIO_ENV (ruta absoluta), p. ej. con SetEnv en Apache.
 *   2. <carpeta padre del docroot>/colegio_config/colegio.env
 *      XAMPP:  C:\xampp\colegio_config\colegio.env
 *      Linux:  /var/www/colegio_config/colegio.env  (si el proyecto está en /var/www/html/…)
 *
 * Plantilla: config/colegio.env.example (versionada, sin secretos).
 */

function config_ruta_env(): string
{
    $porEntorno = getenv('COLEGIO_ENV');
    if (is_string($porEntorno) && $porEntorno !== '') {
        return $porEntorno;
    }
    // core/ → proyecto → docroot (htdocs) → carpeta padre
    return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'colegio_config' . DIRECTORY_SEPARATOR . 'colegio.env';
}

/** Lee KEY=valor; admite comentarios (#), líneas vacías y valores entre comillas. */
function config_cargar(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $ruta = config_ruta_env();
    if (!is_readable($ruta)) {
        error_log("Configuración no encontrada: $ruta");
        http_response_code(500);
        exit('Error de configuración del servidor.');
    }
    $config = [];
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[-1] === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }
        $config[$clave] = $valor;
    }
    return $config;
}

function config(string $clave, ?string $porDefecto = null): ?string
{
    return config_cargar()[$clave] ?? $porDefecto;
}

// Errores: nunca al navegador salvo APP_DEBUG=true (los mensajes de PDO exponen
// rutas, nombres de tablas y SQL). Siempre al log de PHP.
if (config('APP_DEBUG', 'false') !== 'true') {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
