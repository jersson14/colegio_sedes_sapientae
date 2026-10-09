<?php

declare(strict_types=1);

namespace App\Reportes;

use PDO;

/**
 * Consultas de los reportes PDF con parámetros enlazados, sobre la misma conexión PDO que el resto
 * del sistema. Antes cada reporte usaba su propia conexión MySQLi (view/MPDF/conexion.php) y
 * concatenaba los parámetros con real_escape_string.
 */
final class Datos
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param list<int|string> $parametros
     * @return list<array<string, mixed>>
     */
    public function filas(string $sql, array $parametros = []): array
    {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        /** @var list<array<string, mixed>> $filas */
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta->closeCursor();
        return $filas;
    }

    /**
     * @param list<int|string> $parametros
     * @return array<string, mixed>|null la primera fila, o null si no hay
     */
    public function fila(string $sql, array $parametros = []): ?array
    {
        return $this->filas($sql, $parametros)[0] ?? null;
    }
}
