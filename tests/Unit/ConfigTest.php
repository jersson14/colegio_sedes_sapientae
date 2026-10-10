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

    public function testLaZonaHorariaEsLaDePeruSeaCualSeaLaDelServidor(): void
    {
        // tests/bootstrap.php no define APP_ZONA_HORARIA: la de por defecto, no la del php.ini.
        self::assertSame('America/Lima', config_zona_horaria());
        self::assertSame('America/Lima', date_default_timezone_get());
        self::assertSame('-05:00', \App\Core\Conexion::desfase(), 'Perú no tiene horario de verano');
    }
}
