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
        // En consola la salida de Phinx se ve; desde la web (panel de superadministrador) STDOUT no existe:
        // va a un temporal, y al log si la migración falla.
        $salida = PHP_SAPI === 'cli' ? null : tmpfile();
        $descriptores = $salida === null || $salida === false ? [1 => STDOUT, 2 => STDERR] : [1 => $salida, 2 => $salida];
        $proceso = proc_open($comando, $descriptores, $tuberias, self::RAIZ, $entorno);
        $ok = is_resource($proceso) && proc_close($proceso) === 0;
        if (!$ok && is_resource($salida)) {
            rewind($salida);
            error_log("Phinx falló en $base: " . mb_substr((string) stream_get_contents($salida), -2000));
        }
        return $ok;
    }
}
