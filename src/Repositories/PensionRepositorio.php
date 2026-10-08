<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Monto;
use App\Domain\Pension\DatosPension;
use App\Domain\Pension\Pago;

interface PensionRepositorio
{
    /** @return int 1 registrada, 2 ya existe ese mes y año para el nivel */
    public function registrar(DatosPension $pension): int;

    /** @return int 1 modificada, 2 choca con otra del mismo nivel, mes y año, 0 no existe */
    public function modificar(int $id, DatosPension $pension): int;

    /** @return bool false si tiene pagos o no existe */
    public function eliminar(int $id): bool;

    /**
     * Registra los pagos con su ingreso, todos o ninguno.
     *
     * @param list<Pago> $pagos
     * @return bool false si alguno ya estaba pagado (no se registra ninguno)
     */
    public function cobrar(array $pagos, int $cobrador): bool;

    /** @return bool false si el pago no existe */
    public function modificarPago(int $id, Monto $monto, string $motivo): bool;

    /** Anula el ingreso del pago (lo conserva en caja) y libera la pensión. @return bool false si no existe */
    public function anularPago(int $id, int $anuladoPor): bool;
}
