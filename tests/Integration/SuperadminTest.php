<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Superadmin\Auditoria;
use App\Superadmin\CuentasSuperadmin;
use App\Superadmin\PanelInstituciones;
use App\Tenancy\EstadoTenant;
use PDO;
use PHPUnit\Framework\TestCase;

/** Cuentas, auditoría y estados del panel de superadministrador contra la BD maestra (Fase 4.8). */
final class SuperadminTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        if (isset($this->maestro) && $this->maestro->inTransaction()) {
            $this->maestro->rollBack();
        }
    }

    public function testCuentaCreadaEntraYDesactivadaNo(): void
    {
        $cuentas = new CuentasSuperadmin($this->maestro);
        $clave = $cuentas->crear('prueba.integracion', 'Prueba');

        self::assertMatchesRegularExpression('/^[A-Za-z2-9]{20}$/', $clave);
        self::assertSame(['usuario' => 'prueba.integracion', 'nombre' => 'Prueba'], $cuentas->autenticar('prueba.integracion', $clave));
        self::assertNull($cuentas->autenticar('prueba.integracion', 'otra'));
        self::assertNull($cuentas->autenticar('no.existe', $clave));
        self::assertNotSame($clave, $this->maestro->query("SELECT clave_hash FROM superadmins WHERE usuario = 'prueba.integracion'")->fetchColumn());

        self::assertTrue($cuentas->desactivar('prueba.integracion'));
        self::assertFalse($cuentas->activa('prueba.integracion'));
        self::assertNull($cuentas->autenticar('prueba.integracion', $clave), 'una cuenta desactivada no entra');
    }

    public function testUsuarioInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new CuentasSuperadmin($this->maestro))->crear('Con Espacios', 'X');
    }

    public function testCambiarEstadoQuedaAuditadoYUnaBaseCaidaNoTumbaElListado(): void
    {
        $this->maestro->exec("INSERT INTO tenants (slug, razon_social, base_datos, estado) VALUES ('prueba-panel', 'Panel', 'prueba_panel_no_existe', 'ACTIVO')");
        $auditoria = new Auditoria($this->maestro);
        $panel = new PanelInstituciones(
            $this->maestro,
            static fn (string $base): PDO => throw new \PDOException("Unknown database '$base'"),
            $auditoria,
        );

        $panel->cambiarEstado('prueba-panel', EstadoTenant::Suspendido, 'soporte', '10.0.0.1');

        self::assertSame('SUSPENDIDO', $this->maestro->query("SELECT estado FROM tenants WHERE slug = 'prueba-panel'")->fetchColumn());
        $ultima = $auditoria->recientes(1)[0];
        self::assertSame(
            ['soporte', 'ESTADO', 'prueba-panel', 'ACTIVO → SUSPENDIDO', '10.0.0.1'],
            [$ultima['actor'], $ultima['accion'], $ultima['tenant'], $ultima['detalle'], $ultima['ip']]
        );

        $fila = array_values(array_filter($panel->listar(), static fn (array $t): bool => $t['slug'] === 'prueba-panel'))[0];
        self::assertSame('La base no responde', $fila['error']);

        $this->expectException(\DomainException::class);
        $panel->cambiarEstado('no-existe', EstadoTenant::Activo, 'soporte', '');
    }
}
