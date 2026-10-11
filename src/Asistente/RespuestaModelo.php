<?php

declare(strict_types=1);

namespace App\Asistente;

/** Una vuelta del modelo, sin tipos del SDK: lo que el bucle del asistente necesita. */
final class RespuestaModelo
{
    /**
     * @param string $parada end_turn, tool_use, max_tokens, refusal…
     * @param list<array{id: string, nombre: string, entrada: array<string, mixed>}> $llamadas
     * @param mixed $contenido los bloques tal cual, para devolverlos en el turno siguiente
     */
    public function __construct(
        public readonly string $parada,
        public readonly string $texto,
        public readonly array $llamadas,
        public readonly mixed $contenido,
        public readonly int $tokensEntrada,
        public readonly int $tokensSalida,
    ) {
    }
}
