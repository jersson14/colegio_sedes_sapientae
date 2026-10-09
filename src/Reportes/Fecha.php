<?php

declare(strict_types=1);

namespace App\Reportes;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Fechas en español para los reportes, sin depender del locale del servidor. Antes se usaba
 * strftime() (obsoleta desde PHP 8.1): el mes salía en el idioma del sistema operativo
 * («25 de december del 2025» en el reporte de pagos).
 */
final class Fecha
{
    /** «25 de diciembre del 2025» (o «… de 2025» con $conector = 'de'). */
    public static function larga(string $fecha, string $conector = 'del'): string
    {
        $zona = new DateTimeZone('America/Lima');
        $formato = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $zona,
            IntlDateFormatter::GREGORIAN,
            "d 'de' MMMM '$conector' y"
        );
        return (string) $formato->format(new DateTimeImmutable($fecha, $zona));
    }
}
