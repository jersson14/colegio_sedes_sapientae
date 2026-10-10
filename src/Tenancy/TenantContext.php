<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * Única fuente del tenant de la petición. Regla de oro (docs/MULTITENANT.md): ninguna conexión se
 * abre sin un tenant resuelto, así que actual() lanza en lugar de usar un valor por defecto.
 */
final class TenantContext
{
    private static ?Tenant $actual = null;

    public static function establecer(Tenant $tenant): void
    {
        // Cambiar de institución a mitad de una petición mezclaría datos de dos colegios.
        if (self::$actual !== null && self::$actual->slug !== $tenant->slug) {
            throw new \LogicException('El tenant de la petición ya está resuelto y no puede cambiar.');
        }
        self::$actual = $tenant;
    }

    /** @throws TenantNoResuelto */
    public static function actual(): Tenant
    {
        return self::$actual ?? throw new TenantNoResuelto('No hay tenant resuelto para esta petición.');
    }

    public static function resuelto(): bool
    {
        return self::$actual !== null;
    }

    /** Solo para herramientas que recorren todos los tenants y para las pruebas. */
    public static function olvidar(): void
    {
        self::$actual = null;
    }
}
