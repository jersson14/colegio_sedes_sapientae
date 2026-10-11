<?php

declare(strict_types=1);

namespace App\Comercial;

/** Los módulos opcionales activos de una institución, con su límite mensual (null = sin límite). */
final class ModulosInstitucion
{
    /** @param array<string, ?int> $activos valor del módulo => límite mensual */
    public function __construct(private readonly array $activos = [])
    {
    }

    /**
     * Modo único: MODULOS_ACTIVOS=facturacion_electronica,asistente_ia en colegio.env (lo decide quien opera
     * la instalación, igual que el superadministrador en modo múltiple). Los nombres desconocidos se ignoran.
     */
    public static function desdeLista(string $lista, ?int $limiteIa = null): self
    {
        $activos = [];
        foreach (array_filter(array_map('trim', explode(',', strtolower($lista)))) as $nombre) {
            $modulo = ModuloOpcional::tryFrom($nombre);
            if ($modulo !== null) {
                $activos[$modulo->value] = $modulo->tieneLimite() ? $limiteIa : null;
            }
        }
        return new self($activos);
    }

    public function activo(ModuloOpcional $modulo): bool
    {
        return array_key_exists($modulo->value, $this->activos);
    }

    public function limite(ModuloOpcional $modulo): ?int
    {
        return $this->activos[$modulo->value] ?? null;
    }

    /** @return array<string, bool> para el panel (window.MODULOS) */
    public function paraInterfaz(): array
    {
        $todo = [];
        foreach (ModuloOpcional::cases() as $modulo) {
            $todo[$modulo->value] = $this->activo($modulo);
        }
        return $todo;
    }
}
