<?php

declare(strict_types=1);

namespace App\Domain\Matricula;

use InvalidArgumentException;

/**
 * Importe en soles tal como lo guardan las columnas DECIMAL(5,2) de matrícula, pagos e ingresos.
 * Un valor mayor a 999.99 antes se recortaba sin aviso (modo SQL permisivo): ahora se rechaza.
 */
final class Monto
{
    public const MAXIMO = '999.99';

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
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $texto)) {
            throw new InvalidArgumentException("{$campo}: monto no válido (0 a " . self::MAXIMO . ')');
        }
        return new self(number_format((float) $texto, 2, '.', ''));
    }
}
