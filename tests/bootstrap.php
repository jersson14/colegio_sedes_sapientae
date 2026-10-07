<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Configuración aislada: un colegio.env temporal en lugar del real.
$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colegio_tests_' . getmypid();
@mkdir($dir . DIRECTORY_SEPARATOR . 'intentos', 0700, true);
file_put_contents($dir . DIRECTORY_SEPARATOR . 'colegio.env', implode("\n", [
    '# comentario que debe ignorarse',
    'DB_HOST=' . (getenv('DB_HOST') ?: 'localhost'),
    'APP_DEBUG=true',
    'LOGIN_LIMITE_DIR=' . $dir . DIRECTORY_SEPARATOR . 'intentos',
    'VALOR_CON_IGUAL="a=b=c"',
    "COMILLA_SIMPLE='hola mundo'",
    '  CON_ESPACIOS  =  valor  ',
    // Suite Integration: la BD llega por variables de entorno (CI o local).
    'DB_PORT=' . (getenv('DB_PORT') ?: '3306'),
    'DB_NAME=' . (getenv('DB_NAME') ?: ''),
    'DB_USER=' . (getenv('DB_USER') ?: ''),
    'DB_PASS=' . (getenv('DB_PASS') ?: ''),
    '',
]));
putenv('COLEGIO_ENV=' . $dir . DIRECTORY_SEPARATOR . 'colegio.env');
define('TESTS_TMP', $dir);
// Los error_log() del código bajo prueba van a un archivo, no a la salida de PHPUnit.
ini_set('error_log', $dir . DIRECTORY_SEPARATOR . 'php_error.log');

// En producción responder_error() (core/guard.php) envía la respuesta y hace exit.
// Aquí lanza una excepción para poder comprobar el rechazo.
if (!function_exists('responder_error')) {
    function responder_error(int $codigo, string $mensaje): never
    {
        throw new Tests\RespuestaError($mensaje, $codigo);
    }
}
