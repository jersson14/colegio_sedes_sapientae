<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use InvalidArgumentException;

/**
 * Calificación en las escalas que usa el colegio: vigesimal (0 a 20, con un decimal como mucho) o
 * literal (AD, A, B, C). Antes se guardaba cualquier texto de hasta 5 caracteres.
 */
final class ValorNota
{
    private const ESCALA = '/^(?:AD|A|B|C|20(?:\.0)?|1?\d(?:\.\d)?)$/';

    private function __construct(public readonly string $valor)
    {
    }

    /** @throws InvalidArgumentException */
    public static function desde(mixed $texto): self
    {
        $valor = strtoupper(trim((string) $texto));
        if (!preg_match(self::ESCALA, $valor)) {
            throw new InvalidArgumentException("Nota fuera de la escala (0 a 20, o AD, A, B, C): «{$valor}»");
        }
        return new self($valor);
    }
}
