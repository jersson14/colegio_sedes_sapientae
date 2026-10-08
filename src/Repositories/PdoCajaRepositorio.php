<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Caja\Movimiento;
use App\Domain\Caja\MovimientoDiverso;
use PDO;

/** Sobre los SP de ingresos y egresos (migraciones 20261017000000 y 20261019000000). */
final class PdoCajaRepositorio implements CajaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(Movimiento $tipo, MovimientoDiverso $movimiento, int $responsable): bool
    {
        return $this->exito($tipo->procedimientoRegistrar(), [
            $movimiento->indicador, $movimiento->cantidad, $movimiento->monto->valor, $movimiento->observacion, $responsable,
        ]);
    }

    public function modificar(Movimiento $tipo, int $id, MovimientoDiverso $movimiento): bool
    {
        // El último parámetro (USU) se conserva en la firma del SP pero se ignora.
        return $this->exito($tipo->procedimientoModificar(), [
            $id, $movimiento->indicador, $movimiento->cantidad, $movimiento->monto->valor, $movimiento->observacion, 0,
        ]);
    }

    public function anular(Movimiento $tipo, int $id, string $motivo, int $anuladoPor): bool
    {
        return $this->exito($tipo->procedimientoAnular(), [$id, $motivo, $anuladoPor]);
    }

    public function eliminarIndicador(int $id): bool
    {
        return $this->exito('SP_ELIMINAR_INDICADOR', [$id]);
    }

    /** @param list<int|string> $parametros */
    private function exito(string $procedimiento, array $parametros): bool
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        $ok = (int) $consulta->fetchColumn() === 1;
        $consulta->closeCursor();
        return $ok;
    }
}
