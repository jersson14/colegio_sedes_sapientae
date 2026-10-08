<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Caja\Movimiento;
use PDO;

/** Sobre SP_ANULAR_INGRESOS / SP_ANULAR_EGRESOS (migración 20261017000000). */
final class PdoCajaRepositorio implements CajaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function anular(Movimiento $tipo, int $id, string $motivo, int $anuladoPor): bool
    {
        $consulta = $this->pdo->prepare("CALL {$tipo->value}(?, ?, ?)");
        $consulta->execute([$id, $motivo, $anuladoPor]);
        $anulado = (int) $consulta->fetchColumn() === 1;
        $consulta->closeCursor();
        return $anulado;
    }
}
