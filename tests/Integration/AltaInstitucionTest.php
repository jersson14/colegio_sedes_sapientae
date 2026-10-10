<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Conexion;
use App\Domain\Usuario\ResultadoLogin;
use App\Repositories\PdoUsuarioRepositorio;
use App\Services\AutenticarUsuario;
use App\Tenancy\AltaInstitucion;
use App\Tenancy\MigradorPhinx;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\SolicitudAlta;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Alta de una institución (Fase 4.5) contra el servidor real: crea bases de verdad, así que necesita
 * la BD maestra (MAESTRO_DB_NAME) y un usuario que pueda crear bases. Limpia lo que crea.
 */
final class AltaInstitucionTest extends TestCase
{
    private const PREFIJO = 'prueba_alta_';
    private PDO $servidor;
    private PDO $maestro;

    protected function setUp(): void
    {
        if ((getenv('MAESTRO_DB_NAME') ?: '') === '' || (getenv('DB_NAME') ?: '') === '') {
            self::markTestSkipped('Sin BD maestra: define MAESTRO_DB_NAME.');
        }
        $this->servidor = Conexion::administracion();
        $this->servidor->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->maestro = Conexion::maestro();
        $this->limpiar();
    }

    protected function tearDown(): void
    {
        if (isset($this->servidor)) {
            $this->limpiar();
        }
    }

    private function limpiar(): void
    {
        foreach (['facturas', 'consumos', 'suscripciones'] as $dependiente) {
            $this->maestro->exec("DELETE x FROM $dependiente x JOIN tenants t ON t.id = x.tenant_id WHERE t.slug LIKE 'prueba-alta-%'");
        }
        $this->maestro->exec("DELETE FROM tenants WHERE slug LIKE 'prueba-alta-%'");
        foreach ($this->servidor->query("SHOW DATABASES LIKE 'prueba\\_alta\\_%'")->fetchAll(PDO::FETCH_COLUMN) as $base) {
            $this->servidor->exec("DROP DATABASE `$base`");
        }
    }

    private function solicitud(string $sufijo, ?string $base = null): SolicitudAlta
    {
        return new SolicitudAlta(
            'prueba-alta-' . $sufijo,
            'Colegio Alta ' . $sufijo,
            'direccion@example.com',
            '12345678',
            'Ana María',
            'Pérez Soto',
            baseDatos: $base ?? self::PREFIJO . $sufijo
        );
    }

    /** @param ?\Closure(string): bool $migrar */
    private function alta(?\Closure $migrar = null): AltaInstitucion
    {
        return new AltaInstitucion(
            $this->servidor,
            $this->maestro,
            static fn (string $base): PDO => Conexion::administracion($base),
            $migrar ?? static fn (string $base): bool => MigradorPhinx::ejecutar('migrate', $base),
        );
    }

    private function existeBase(string $base): bool
    {
        $q = $this->servidor->prepare('SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $q->execute([$base]);
        return $q->fetchColumn() !== false;
    }

    public function testAltaCompletaYElAdministradorEntra(): void
    {
        ob_start(); // la salida de Phinx
        $clave = $this->alta()->ejecutar($this->solicitud('a'));
        ob_end_clean();

        $tenant = (new PdoRepositorioTenants($this->maestro))->porSlug('prueba-alta-a');
        self::assertNotNull($tenant);
        self::assertSame(self::PREFIJO . 'a', $tenant->baseDatos);
        self::assertSame('PRUEBA', $tenant->estado->value, 'empieza en prueba');

        $base = Conexion::administracion(self::PREFIJO . 'a');
        self::assertSame([1, 2, 3, 4, 5, 9], array_map('intval', $base->query('SELECT Id_rol FROM roles ORDER BY Id_rol')->fetchAll(PDO::FETCH_COLUMN)));
        self::assertSame('COLEGIO ALTA A', $base->query('SELECT emp_razon FROM empresa WHERE empresa_id = 1')->fetchColumn());

        $login = (new AutenticarUsuario(new PdoUsuarioRepositorio($base)))->ejecutar('admin', $clave);
        self::assertSame(ResultadoLogin::Correcto, $login->resultado);
        self::assertSame('ADMINISTRADOR', $login->cuenta['tipo_rol'] ?? null);
        self::assertMatchesRegularExpression('/^[A-Za-z2-9]{16}$/', $clave);
        self::assertStringNotContainsString($clave, (string) $base->query('SELECT usu_contra FROM usuario')->fetchColumn(), 'la clave no se guarda en claro');
    }

    public function testSiLasMigracionesFallanNoQuedaNada(): void
    {
        try {
            $this->alta(static fn (string $base): bool => false)->ejecutar($this->solicitud('b'));
            self::fail('el alta debía fallar');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('migraciones', $e->getMessage());
        }
        self::assertFalse($this->existeBase(self::PREFIJO . 'b'), 'la base creada se borra');
        self::assertNull((new PdoRepositorioTenants($this->maestro))->porSlug('prueba-alta-b'), 'ni se registra');
    }

    public function testNuncaTocaUnaBaseQueYaExistia(): void
    {
        $this->servidor->exec('CREATE DATABASE `' . self::PREFIJO . 'existente`');
        $this->servidor->exec('CREATE TABLE `' . self::PREFIJO . 'existente`.datos_ajenos (id INT)');

        try {
            $this->alta()->ejecutar($this->solicitud('c', self::PREFIJO . 'existente'));
            self::fail('el alta debía negarse');
        } catch (\DomainException $e) {
            self::assertStringContainsString('ya existe', $e->getMessage());
        }
        self::assertTrue($this->existeBase(self::PREFIJO . 'existente'), 'la base ajena sigue ahí');
        self::assertSame('0', (string) $this->servidor->query('SELECT COUNT(*) FROM `' . self::PREFIJO . 'existente`.datos_ajenos')->fetchColumn());
    }

    public function testSlugRepetido(): void
    {
        $this->maestro->exec("INSERT INTO tenants (slug, razon_social, base_datos) VALUES ('prueba-alta-d', 'Otro', 'prueba_alta_otra')");
        $this->expectException(\DomainException::class);
        $this->alta(static fn (string $base): bool => true)->ejecutar($this->solicitud('d'));
    }
}
