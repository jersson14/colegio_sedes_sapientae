<?php

declare(strict_types=1);

namespace Tests\Unit\Reportes;

use App\Reportes\Fecha;
use PHPUnit\Framework\TestCase;

final class FechaTest extends TestCase
{
    public function testEnEspanolSinDependerDelLocaleDelServidor(): void
    {
        $anterior = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'C'); // como un servidor sin es_ES: strftime() daba «december»
        try {
            self::assertSame('25 de diciembre del 2025', Fecha::larga('2025-12-25 10:30:00'));
            self::assertSame('1 de marzo de 2026', Fecha::larga('2026-03-01', 'de'));
        } finally {
            setlocale(LC_TIME, (string) $anterior);
        }
    }
}
