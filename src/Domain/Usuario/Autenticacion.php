<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/** Resultado del login más la fila de la cuenta (solo cuando es Correcto), para crear la sesión. */
final class Autenticacion
{
    /** @param array<string, mixed>|null $cuenta */
    private function __construct(
        public readonly ResultadoLogin $resultado,
        public readonly ?array $cuenta,
    ) {
    }

    /** @param array<string, mixed> $cuenta */
    public static function correcta(array $cuenta): self
    {
        return new self(ResultadoLogin::Correcto, $cuenta);
    }

    public static function fallida(ResultadoLogin $resultado): self
    {
        return new self($resultado, null);
    }
}
