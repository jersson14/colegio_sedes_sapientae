<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * Estado comercial de la institución (docs/PLAN_DE_TRABAJO.md, 4B.3). Las restricciones finas de
 * MOROSO (solo lectura) llegan con el empaquetado comercial; aquí solo se decide si entra o no.
 */
enum EstadoTenant: string
{
    case Prueba = 'PRUEBA';
    case Activo = 'ACTIVO';
    case Moroso = 'MOROSO';
    case Suspendido = 'SUSPENDIDO';
    case Cancelado = 'CANCELADO';

    /** Nunca se corta de golpe el acceso a los datos académicos por una factura vencida. */
    public function permiteAcceso(): bool
    {
        return in_array($this, [self::Prueba, self::Activo, self::Moroso], true);
    }
}
