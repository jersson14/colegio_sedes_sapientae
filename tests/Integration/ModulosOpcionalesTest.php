<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Comercial\ModuloOpcional;
use App\Comercial\PdoRepositorioModulos;
use PDO;
use PHPUnit\Framework\TestCase;

/** Módulos opcionales por institución en la BD maestra (migración maestra 20261101000000). */
final class ModulosOpcionalesTest extends TestCase
{
    private PDO $maestro;

    protected function setUp(): void
    {
        $nombre = getenv('MAESTRO_DB_NAME') ?: '';
        if ($nombre === '' || (getenv('DB_NAME') ?: '') === '') {
            self::markTestSkipped('Sin BD maestra: define MAESTRO_DB_NAME.');
        }
        $this->maestro = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s', getenv('DB_HOST') ?: 'localhost', (int) (getenv('DB_PORT') ?: 3306), $nombre),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $this->maestro->beginTransaction();
        $this->maestro->exec("INSERT INTO tenants (slug, razon_social, base_datos, estado) VALUES ('prueba-modulos', 'Módulos', 'prueba_modulos', 'ACTIVO')");
    }

    protected function tearDown(): void
    {
        if (isset($this->maestro) && $this->maestro->inTransaction()) {
            $this->maestro->rollBack();
        }
    }

    public function testActivarYDesactivar(): void
    {
        $repo = new PdoRepositorioModulos($this->maestro);
        self::assertFalse($repo->de('prueba-modulos')->activo(ModuloOpcional::AsistenteIa), 'sin activar, nada');

        $repo->guardar('prueba-modulos', ModuloOpcional::AsistenteIa, true, 500, 'admin@plataforma');
        $repo->guardar('prueba-modulos', ModuloOpcional::FacturacionElectronica, true, 99, 'admin@plataforma');
        $modulos = $repo->de('prueba-modulos');
        self::assertTrue($modulos->activo(ModuloOpcional::AsistenteIa));
        self::assertSame(500, $modulos->limite(ModuloOpcional::AsistenteIa));
        self::assertNull($modulos->limite(ModuloOpcional::FacturacionElectronica), 'la facturación no guarda límite');
        self::assertSame(['activo' => true, 'limite' => 500], $repo->todos()['prueba-modulos']['asistente_ia']);

        $repo->guardar('prueba-modulos', ModuloOpcional::AsistenteIa, false, null, 'admin@plataforma');
        self::assertFalse($repo->de('prueba-modulos')->activo(ModuloOpcional::AsistenteIa));
        self::assertTrue($repo->de('prueba-modulos')->activo(ModuloOpcional::FacturacionElectronica), 'cada módulo por separado');
    }

    public function testInstitucionInexistente(): void
    {
        $this->expectException(\DomainException::class);
        (new PdoRepositorioModulos($this->maestro))->guardar('no-existe', ModuloOpcional::AsistenteIa, true, null, 'x');
    }
}
