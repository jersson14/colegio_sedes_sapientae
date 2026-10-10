<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\EstadoTenant;
use App\Tenancy\ModoTenant;
use App\Tenancy\RepositorioTenants;
use App\Tenancy\ResolverTenant;
use App\Tenancy\Tenant;
use App\Tenancy\TenantNoEncontrado;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolverTenantTest extends TestCase
{
    private RepositorioTenantsEnMemoria $registro;

    protected function setUp(): void
    {
        $this->registro = new RepositorioTenantsEnMemoria([
            new Tenant('sapientae', 'sge_sapientae', EstadoTenant::Activo),
            new Tenant('instituto-x', 'sge_instituto_x', EstadoTenant::Prueba),
            new Tenant('moroso', 'sge_moroso', EstadoTenant::Moroso),
            new Tenant('suspendido', 'sge_suspendido', EstadoTenant::Suspendido),
            new Tenant('cancelado', 'sge_cancelado', EstadoTenant::Cancelado),
        ], ['intranet.institutox.edu.pe' => 'instituto-x']);
    }

    private function multiple(): ResolverTenant
    {
        $registro = $this->registro;
        return new ResolverTenant(ModoTenant::Multiple, repositorio: static fn (): RepositorioTenants => $registro, dominioBase: 'miapp.pe');
    }

    public function testModoUnicoDevuelveSiempreSuInstitucionSinMirarElHost(): void
    {
        $unico = new Tenant('principal', 'colegio_sedes', EstadoTenant::Activo);
        $resolver = new ResolverTenant(ModoTenant::Unico, $unico);

        self::assertSame($unico, $resolver->resolver('otro.miapp.pe'));
        self::assertSame($unico, $resolver->resolver(null));
    }

    public function testElSubdominioEligeLaBase(): void
    {
        self::assertSame('sge_sapientae', $this->multiple()->resolver('sapientae.miapp.pe')->baseDatos);
    }

    public function testIgnoraMayusculasPuertoYPuntoFinal(): void
    {
        self::assertSame('sapientae', $this->multiple()->resolver('SapienTae.MiApp.pe.:8443')->slug);
    }

    public function testDominioPropio(): void
    {
        self::assertSame('instituto-x', $this->multiple()->resolver('intranet.institutox.edu.pe')->slug);
    }

    public function testElMorosoSigueEntrando(): void
    {
        self::assertSame('moroso', $this->multiple()->resolver('moroso.miapp.pe')->slug);
    }

    /** Inexistente, suspendido y cancelado responden igual: no se revela qué instituciones existen. */
    #[DataProvider('hostsSinAcceso')]
    public function testSinAccesoEsSiempreNoEncontrado(?string $host): void
    {
        $mensaje = null;
        try {
            $this->multiple()->resolver($host);
        } catch (TenantNoEncontrado $e) {
            $mensaje = $e->getMessage();
        }
        self::assertNotNull($mensaje, "«{$host}» no debía resolverse");
        self::assertNotContains('sapientae', $this->registro->consultados, 'un host ajeno no consulta otro slug');
    }

    /** @return array<string, array{?string}> */
    public static function hostsSinAcceso(): array
    {
        return [
            'inexistente' => ['nadie.miapp.pe'],
            'suspendido' => ['suspendido.miapp.pe'],
            'cancelado' => ['cancelado.miapp.pe'],
            'dominio base sin subdominio' => ['miapp.pe'],
            'dos niveles' => ['sapientae.otro.miapp.pe'],
            'dominio que solo termina igual' => ['sapientae.falsomiapp.pe'],
            'sin host' => [null],
            'vacío' => [''],
            'caracteres raros' => ["sapientae.miapp.pe\0"],
            'ruta en el host' => ['sapientae.miapp.pe/../x'],
            'IPv6' => ['[::1]'],
        ];
    }

    public function testSlugDesdeHost(): void
    {
        self::assertSame('a-1', ResolverTenant::slugDesdeHost('a-1.miapp.pe', 'miapp.pe'));
        self::assertNull(ResolverTenant::slugDesdeHost('-a.miapp.pe', 'miapp.pe'));
        self::assertNull(ResolverTenant::slugDesdeHost('a.miapp.pe', ''));
    }

    public function testModoMultipleSinRegistroEsUnErrorDeConfiguracion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ResolverTenant(ModoTenant::Multiple);
    }

    public function testModoInvalidoNoCaeEnSilencioAOtroModo(): void
    {
        self::assertSame(ModoTenant::Unico, ModoTenant::desdeConfig(null));
        self::assertSame(ModoTenant::Multiple, ModoTenant::desdeConfig(' Multiple '));
        $this->expectException(\UnexpectedValueException::class);
        ModoTenant::desdeConfig('multi');
    }

    public function testNombreDeBaseQueAlteraElDsnSeRechaza(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Tenant('x', 'sge;host=evil', EstadoTenant::Activo);
    }
}
