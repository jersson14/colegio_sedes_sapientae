<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use App\Support\Texto;
use InvalidArgumentException;

/**
 * Conclusiones y competencias que escribe el docente. Se guardan escapadas, como el resto de los
 * formularios: las tablas del panel las pintan como HTML, así que sin escapar eran XSS almacenado.
 * No se pasan a mayúsculas (son descriptivas).
 */
final class TextoLibre
{
    /** @throws InvalidArgumentException si escapado no cabe en la columna (antes se truncaba) */
    public static function desde(mixed $texto, string $campo, int $maximo = 255): string
    {
        $valor = htmlspecialchars(trim((string) $texto), ENT_QUOTES, 'UTF-8');
        Texto::exigirLargo($campo, $valor, $maximo);
        return $valor;
    }
}
