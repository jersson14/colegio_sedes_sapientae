<?php

declare(strict_types=1);

namespace App\Domain\Caja;

/** Tipo de movimiento de caja, con el procedimiento que lo anula. */
enum Movimiento: string
{
    case Ingreso = 'SP_ANULAR_INGRESOS';
    case Egreso = 'SP_ANULAR_EGRESOS';
}
