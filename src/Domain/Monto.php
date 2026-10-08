<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * Importe en soles tal como lo guardan las columnas DECIMAL(10,2) de matrícula, pagos e ingresos
 * (migración 20261015000000; antes DECIMAL(5,2) recortaba a 999.99 sin aviso). Lo que no cabe se
 * rechaza en lugar de recortarse.
 */
final class Monto
{
    public const MAXIMO = '99999999.99';

    private function __construct(public readonly string $valor)
    {
    }

    /**
     * Vacío cuenta como 0, como hacía la BD (el formulario deshabilita los montos que no aplican).
     *
     * @throws InvalidArgumentException
     */
    public static function desde(mixed $texto, string $campo): self
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return new self('0.00');
        }
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $texto)) {
            throw new InvalidArgumentException("{$campo}: monto no válido (0 a " . self::MAXIMO . ')');
        }
        // Como texto, sin pasar por float (que pierde precisión en montos grandes).
        [$enteros, $decimales] = array_pad(explode('.', $texto), 2, '');
        return new self((ltrim($enteros, '0') ?: '0') . '.' . str_pad($decimales, 2, '0'));
    }
}
