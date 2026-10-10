<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Conexion;
use App\Tenancy\EstadoTenant;
use App\Tenancy\ModoTenant;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\RepositorioTenants;
use App\Tenancy\ResolverTenant;
use App\Tenancy\TenantNoEncontrado;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Registro de instituciones contra la BD maestra creada con phinx_maestro.php (Fase 4).
 * Sin MAESTRO_DB_NAME en el entorno, se omite.
 */
final class TenantsMaestroTest extends TestCase
{
    private PDO $maestro;

    protected function setUp(): void
    {
        $nombre = getenv('MAESTRO_DB_NAME') ?: '';
        if ($nombre === '' || (getenv('DB_NAME') ?: '') === '') {
            self::markTestSkipped('Sin BD maestra: define MAESTRO_DB_NAME (vendor/bin/phinx migrate -c phinx_maestro.php).');
        }
        $this->maestro = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s', getenv('DB_HOST') ?: 'localhost', (int) (getenv('DB_PORT') ?: 3306), $nombre),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $this->maestro->beginTransaction();
        $alta = $this->maestro->prepare('INSERT INTO tenants (slug, dominio, razon_social, base_datos, estado) VALUES (?, ?, ?, ?, ?)');
        $alta->execute(['prueba-a', null, 'Colegio A', 'sge_prueba_a', 'ACTIVO']);
        $alta->execute(['prueba-b', 'intranet.colegio-b.edu.pe', 'Colegio B', 'sge_prueba_b', 'SUSPENDIDO']);
    }

    protected function tearDown(): void
    {
        if (isset($this->maestro) && $this->maestro->inTransaction()) {
            $this->maestro->rollBack();
        }
    }

    public function testLeeElRegistro(): void
    {
        $repositorio = new PdoRepositorioTenants($this->maestro);

        $a = $repositorio->porSlug('prueba-a');
        self::assertNotNull($a);
        self::assertSame(['sge_prueba_a', EstadoTenant::Activo, 'Colegio A'], [$a->baseDatos, $a->estado, $a->razonSocial]);
        self::assertSame('prueba-b', $repositorio->porDominio('intranet.colegio-b.edu.pe')?->slug);
        self::assertNull($repositorio->porSlug('no-existe'));
        self::assertContains('prueba-a', array_map(static fn ($t) => $t->slug, $repositorio->todos()));
    }

    public function testElSlugYLaBaseSonUnicos(): void
    {
        $this->expectException(\PDOException::class);
        $this->maestro->prepare('INSERT INTO tenants (slug, razon_social, base_datos) VALUES (?, ?, ?)')
            ->execute(['otro', 'Otro', 'sge_prueba_a']);
    }

    public function testUnaInstitucionNuevaEmpiezaEnPrueba(): void
    {
        $this->maestro->prepare('INSERT INTO tenants (slug, razon_social, base_datos) VALUES (?, ?, ?)')
            ->execute(['nueva', 'Nueva', 'sge_nueva']);
        self::assertSame(EstadoTenant::Prueba, (new PdoRepositorioTenants($this->maestro))->porSlug('nueva')?->estado);
    }

    public function testElResolverNoDejaEntrarAlSuspendidoNiPorSuDominio(): void
    {
        $repositorio = new PdoRepositorioTenants($this->maestro);
        $resolver = new ResolverTenant(ModoTenant::Multiple, repositorio: static fn (): RepositorioTenants => $repositorio, dominioBase: 'miapp.pe');

        self::assertSame('sge_prueba_a', $resolver->resolver('prueba-a.miapp.pe')->baseDatos);
        $this->expectException(TenantNoEncontrado::class);
        $resolver->resolver('intranet.colegio-b.edu.pe');
    }

    public function testConexionMaestroAbreLaBaseConfigurada(): void
    {
        // tests/bootstrap.php copia MAESTRO_DB_NAME del entorno a su colegio.env.
        self::assertSame(getenv('MAESTRO_DB_NAME'), Conexion::maestro()->query('SELECT DATABASE()')->fetchColumn());
    }
}
