<?php

declare(strict_types=1);

namespace App\Core;

use App\Tenancy\TenantContext;
use PDO;

/**
 * Única fábrica de la conexión PDO. model/model_conexion.php la usa también, así que el código
 * heredado y el nuevo comparten exactamente la misma configuración de sesión.
 *
 * La base sale del tenant resuelto (Fase 4): en modo único es DB_NAME; en modo múltiple, la que la
 * BD maestra asigna a la institución. Sin tenant resuelto no hay conexión (TenantNoResuelto).
 */
final class Conexion
{
    /**
     * @throws \PDOException si no se puede conectar
     * @throws \App\Tenancy\TenantNoResuelto si no se resolvió antes la institución
     */
    public static function crear(): PDO
    {
        require_once __DIR__ . '/../../core/config.php';
        $pdo = self::abrir(TenantContext::actual()->baseDatos, (string) config('DB_USER', ''), (string) config('DB_PASS', ''));
        // El sistema se construyó sobre el sql_mode permisivo de XAMPP (p. ej. '' en un parámetro INT
        // se toma como 0). Con el modo estricto por defecto de otros servidores esas llamadas fallan
        // con 500: se fija explícitamente para no depender de la configuración del servidor.
        $pdo->prepare('SET SESSION sql_mode = ?')
            ->execute([config('DB_SQL_MODE', 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION')]);
        // Solo pruebas: congela NOW()/CURDATE() para que los SP que filtran por fecha sean deterministas.
        if (config('APP_ENTORNO') === 'prueba' && config('DB_FECHA_PRUEBA')) {
            $pdo->prepare('SET SESSION timestamp = UNIX_TIMESTAMP(?)')->execute([config('DB_FECHA_PRUEBA')]);
        }
        return $pdo;
    }

    /**
     * BD maestra (registro de instituciones), solo en modo múltiple. Puede tener un usuario propio
     * (MAESTRO_DB_USER) para que el de la aplicación no lea ni escriba el registro.
     *
     * @throws \PDOException si no se puede conectar
     */
    public static function maestro(): PDO
    {
        require_once __DIR__ . '/../../core/config.php';
        $usuario = (string) config('MAESTRO_DB_USER', '');
        return self::abrir(
            (string) config('MAESTRO_DB_NAME', 'sge_maestro'),
            $usuario !== '' ? $usuario : (string) config('DB_USER', ''),
            $usuario !== '' ? (string) config('MAESTRO_DB_PASS', '') : (string) config('DB_PASS', ''),
        );
    }

    /**
     * Conexión con permisos DDL (DB_MIGRACION_USER; si falta, DB_USER) para herramientas de consola que
     * crean bases: el alta de una institución. Sin $base, al servidor sin base seleccionada.
     *
     * @throws \PDOException si no se puede conectar
     */
    public static function administracion(?string $base = null): PDO
    {
        require_once __DIR__ . '/../../core/config.php';
        $usuario = (string) config('DB_MIGRACION_USER', '');
        return self::abrir(
            $base,
            $usuario !== '' ? $usuario : (string) config('DB_USER', ''),
            $usuario !== '' ? (string) config('DB_MIGRACION_PASS', '') : (string) config('DB_PASS', ''),
        );
    }

    private static function abrir(?string $base, string $usuario, string $clave): PDO
    {
        require_once __DIR__ . '/../../core/config.php';
        $dsn = sprintf('mysql:host=%s;port=%d', config('DB_HOST', 'localhost'), (int) config('DB_PORT', '3306'))
            . ($base !== null ? ';dbname=' . $base : '');
        $pdo = new PDO($dsn, $usuario, $clave);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('set names utf8');
        return $pdo;
    }
}
