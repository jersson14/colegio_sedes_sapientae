<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * Ejecuta Phinx sobre una base concreta, en un proceso aparte y con las credenciales DDL de colegio.env
 * por variables de entorno (nunca en la línea de comandos, que otros usuarios del servidor pueden ver).
 */
final class MigradorPhinx
{
    private const RAIZ = __DIR__ . '/../..';

    /** @param 'migrate'|'status' $accion */
    public static function ejecutar(string $accion, string $base, bool $maestra = false): bool
    {
        require_once self::RAIZ . '/core/config.php';
        $usuario = (string) config('DB_MIGRACION_USER', '');
        // Lo de aquí manda sobre el entorno heredado (un DB_NAME exportado no debe elegir otra base).
        $entorno = [
            'DB_HOST' => (string) config('DB_HOST', 'localhost'),
            'DB_PORT' => (string) config('DB_PORT', '3306'),
            'DB_NAME' => $base,
            'MAESTRO_DB_NAME' => $base,
            'DB_MIGRACION_USER' => $usuario !== '' ? $usuario : (string) config('DB_USER', ''),
            'DB_MIGRACION_PASS' => $usuario !== '' ? (string) config('DB_MIGRACION_PASS', '') : (string) config('DB_PASS', ''),
        ] + getenv();
        $comando = [
            PHP_BINARY,
            self::RAIZ . '/vendor/robmorgan/phinx/bin/phinx',
            $accion,
            '-c',
            self::RAIZ . ($maestra ? '/phinx_maestro.php' : '/phinx.php'),
            '--no-interaction',
        ];
        $proceso = proc_open($comando, [1 => STDOUT, 2 => STDERR], $tuberias, self::RAIZ, $entorno);
        return is_resource($proceso) && proc_close($proceso) === 0;
    }
}
