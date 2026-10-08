<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Caja\Movimiento;

interface CajaRepositorio
{
    /**
     * Anula un movimiento VALIDO dejando constancia de quién lo anuló; quién lo cobró o pagó no cambia.
     *
     * @return bool false si no existe o ya estaba anulado
     */
    public function anular(Movimiento $tipo, int $id, string $motivo, int $anuladoPor): bool;
}
