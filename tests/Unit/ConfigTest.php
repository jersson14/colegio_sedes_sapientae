<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/config.php';

/**
 * Lector de colegio.env (Fase 0.4). El archivo lo genera tests/bootstrap.php.
 */
final class ConfigTest extends TestCase
{
    public function testLeeValoresSimples(): void
    {
        self::assertSame(getenv('DB_HOST') ?: 'localhost', config('DB_HOST'));
    }

    public function testComillasDoblesConSignoIgualDentro(): void
    {
        self::assertSame('a=b=c', config('VALOR_CON_IGUAL'));
    }

    public function testComillasSimples(): void
    {
        self::assertSame('hola mundo', config('COMILLA_SIMPLE'));
    }

    public function testRecortaEspaciosDeClaveYValor(): void
    {
        self::assertSame('valor', config('CON_ESPACIOS'));
    }

    public function testClaveAusenteDevuelveElValorPorDefecto(): void
    {
        self::assertNull(config('NO_EXISTE'));
        self::assertSame('3306', config('NO_EXISTE', '3306'));
    }

    public function testLosComentariosNoSonClaves(): void
    {
        self::assertNull(config('# comentario que debe ignorarse'));
    }

    public function testLaRutaVieneDeLaVariableDeEntorno(): void
    {
        self::assertSame(getenv('COLEGIO_ENV'), config_ruta_env());
    }
}
