<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Caja\Movimiento;
use App\Domain\Caja\MovimientoDiverso;

interface CajaRepositorio
{
    /** @return bool false si el indicador no es del tipo del movimiento */
    public function registrar(Movimiento $tipo, MovimientoDiverso $movimiento, int $responsable): bool;

    /**
     * Quién cobró o pagó no cambia.
     *
     * @return bool false si no existe, está anulado, viene de un pago de pensión o el indicador no corresponde
     */
    public function modificar(Movimiento $tipo, int $id, MovimientoDiverso $movimiento): bool;

    /**
     * Anula un movimiento VALIDO dejando constancia de quién lo anuló; quién lo cobró o pagó no cambia.
     *
     * @return bool false si no existe o ya estaba anulado
     */
    public function anular(Movimiento $tipo, int $id, string $motivo, int $anuladoPor): bool;

    /** @return bool false si el indicador está en uso o no existe */
    public function eliminarIndicador(int $id): bool;
}
