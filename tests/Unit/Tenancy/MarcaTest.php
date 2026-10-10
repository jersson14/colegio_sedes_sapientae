<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\Marca;
use App\Tenancy\ModoTenant;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
final class MarcaTest extends TestCase
{
    private function empresa(string $razon, string $logo): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE empresa (empresa_id INTEGER, emp_razon TEXT, emp_logo TEXT)');
        $pdo->prepare('INSERT INTO empresa VALUES (1, ?, ?)')->execute([$razon, $logo]);
        return $pdo;
    }

    public function testModoUnicoConservaLasImagenesDeLaInstalacionYTomaElNombreDeLaBd(): void
    {
        $marca = Marca::deLaInstitucion($this->empresa('COLEGIO X', 'controller/empresa/FOTOS/a.png'), ModoTenant::Unico, static fn (): bool => true);

        self::assertSame('COLEGIO X', $marca->nombre);
        self::assertSame(['img/logo1.png', 'img/logo.jpeg', 'img/icono.jpeg', 'img/fondo.jpeg'], [$marca->logoAcceso, $marca->logoPanel, $marca->icono, $marca->fondoAcceso]);
    }

    public function testModoMultipleUsaElLogoDelColegioPorElEndpointPublico(): void
    {
        $marca = Marca::deLaInstitucion($this->empresa('COLEGIO B', 'controller/empresa/FOTOS/b.png'), ModoTenant::Multiple, static fn (): bool => true);

        self::assertSame('COLEGIO B', $marca->nombre);
        self::assertStringStartsWith(Marca::LOGO_PUBLICO . '?v=', $marca->logoAcceso);
        self::assertSame($marca->logoAcceso, $marca->icono);
        self::assertNull($marca->fondoAcceso, 'nunca el fondo de la instalación original');
    }

    public function testModoMultipleSinLogoNoMuestraLaMarcaDeOtroColegio(): void
    {
        foreach (['', 'controller/empresa/FOTOS/no_existe.png'] as $logo) {
            $marca = Marca::deLaInstitucion($this->empresa('COLEGIO C', $logo), ModoTenant::Multiple, static fn (): bool => false);
            self::assertSame(Marca::NEUTRA, $marca->logoAcceso);
            self::assertStringNotContainsString('img/logo', $marca->logoPanel);
        }
    }

    public function testElLogoCambiaDeVersionAlCambiarDeArchivo(): void
    {
        $a = Marca::deLaInstitucion($this->empresa('X', 'controller/empresa/FOTOS/a.png'), ModoTenant::Multiple, static fn (): bool => true);
        $b = Marca::deLaInstitucion($this->empresa('X', 'controller/empresa/FOTOS/b.png'), ModoTenant::Multiple, static fn (): bool => true);
        self::assertNotSame($a->logoAcceso, $b->logoAcceso);
    }

    public function testSinBdOSinTablaElAccesoSeMuestraIgual(): void
    {
        self::assertSame(Marca::NOMBRE_POR_DEFECTO, Marca::deLaInstitucion(null, ModoTenant::Multiple, static fn (): bool => true)->nombre);
        $vacia = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        self::assertSame(Marca::NEUTRA, Marca::deLaInstitucion($vacia, ModoTenant::Multiple, static fn (): bool => true)->logoAcceso);
    }
}
