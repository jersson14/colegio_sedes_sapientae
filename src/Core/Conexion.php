<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Única fábrica de la conexión PDO. model/model_conexion.php la usa también, así que el código
 * heredado y el nuevo comparten exactamente la misma configuración de sesión.
 */
final class Conexion
{
    /** @throws \PDOException si no se puede conectar */
    public static function crear(): PDO
    {
        require_once __DIR__ . '/../../core/config.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            config('DB_HOST', 'localhost'),
            (int) config('DB_PORT', '3306'),
            config('DB_NAME', 'colegio')
        );
        $pdo = new PDO($dsn, (string) config('DB_USER', ''), (string) config('DB_PASS', ''));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('set names utf8');
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
}
