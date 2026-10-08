<?php

declare(strict_types=1);

namespace App\Domain\Caja;

/** Tipo de movimiento de caja, con sus procedimientos. */
enum Movimiento: string
{
    case Ingreso = 'INGRESO';
    case Egreso = 'EGRESO';

    public function procedimientoRegistrar(): string
    {
        return $this === self::Ingreso ? 'SP_REGISTRAR_INGRESOS' : 'SP_REGISTRAR_EGRESOS';
    }

    public function procedimientoModificar(): string
    {
        return $this === self::Ingreso ? 'SP_MODIFICAR_INGRESOS' : 'SP_MODIFICAR_EGRESOS';
    }

    public function procedimientoAnular(): string
    {
        return $this === self::Ingreso ? 'SP_ANULAR_INGRESOS' : 'SP_ANULAR_EGRESOS';
    }
}
