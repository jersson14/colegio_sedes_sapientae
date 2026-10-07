<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base de las pruebas contra una BD real creada con Phinx.
 * Sin DB_NAME en el entorno, las pruebas se omiten (no fallan).
 * Cada prueba corre dentro de una transacción que se revierte.
 */
abstract class BaseDatosTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        if ((getenv('DB_NAME') ?: '') === '') {
            self::markTestSkipped('Sin BD de pruebas: define DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS.');
        }
        require_once __DIR__ . '/../../core/pertenencia.php';
        $this->pdo = pertenencia_pdo();
        $this->pdo->exec("SET SESSION sql_mode = ''");      // los fixtures solo rellenan las claves
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 0');
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $_SESSION = [];
    }

    /** @param array<string, int|string> $fila */
    protected function insertar(string $tabla, array $fila): void
    {
        $cols = implode(', ', array_map(fn ($c) => "`$c`", array_keys($fila)));
        $marcas = implode(', ', array_fill(0, count($fila), '?'));
        $this->pdo->prepare("INSERT INTO `$tabla` ($cols) VALUES ($marcas)")->execute(array_values($fila));
    }

    protected function comoUsuario(string $rol, int $usuId, string $dni = ''): void
    {
        $_SESSION = ['S_ROL' => $rol, 'S_ID' => (string) $usuId, 'S_DNI' => $dni];
    }
}
