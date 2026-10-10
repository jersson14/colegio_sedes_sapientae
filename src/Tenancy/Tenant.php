<?php

declare(strict_types=1);

namespace App\Tenancy;

/** Una institución y la base de datos que le pertenece (modelo D de docs/MULTITENANT.md). */
final class Tenant
{
    public function __construct(
        public readonly string $slug,
        public readonly string $baseDatos,
        public readonly EstadoTenant $estado,
        public readonly string $razonSocial = '',
        /** Desde cuándo está SUSPENDIDO (abre la ventana de exportación de sus datos, Fase 4B.3). */
        public readonly ?\DateTimeImmutable $suspendidoDesde = null,
    ) {
        if (!self::slugValido($slug)) {
            throw new \InvalidArgumentException("Slug de tenant inválido: «{$slug}».");
        }
        // El nombre de la base va en el DSN: solo caracteres que no puedan alterarlo.
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $baseDatos) !== 1) {
            throw new \InvalidArgumentException("Nombre de base inválido para el tenant «{$slug}».");
        }
    }

    /** Una etiqueta DNS: minúsculas, dígitos y guiones, sin guion al principio ni al final. */
    public static function slugValido(string $slug): bool
    {
        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug) === 1;
    }
}
