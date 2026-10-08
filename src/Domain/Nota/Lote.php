<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use InvalidArgumentException;

/** Lectura del campo «registros» (un JSON con una lista de objetos) que envía el panel. */
final class Lote
{
    /**
     * @return list<array<mixed>>
     * @throws InvalidArgumentException si no es una lista no vacía de objetos
     */
    public static function desdeJson(mixed $json): array
    {
        $registros = json_decode((string) $json, true);
        if (!is_array($registros) || $registros === [] || !array_is_list($registros)) {
            throw new InvalidArgumentException('No se recibieron registros');
        }
        foreach ($registros as $r) {
            if (!is_array($r)) {
                throw new InvalidArgumentException('Registro con formato incorrecto');
            }
        }
        /** @var list<array<mixed>> $registros */
        return $registros;
    }

    /** @throws InvalidArgumentException */
    public static function idPositivo(mixed $valor, string $campo): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException("Identificador de {$campo} no válido");
        }
        return $id;
    }
}
