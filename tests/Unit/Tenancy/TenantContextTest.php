<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\EstadoTenant;
use App\Tenancy\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantNoResuelto;
use PHPUnit\Framework\TestCase;

final class TenantContextTest extends TestCase
{
    private ?Tenant $previo = null;

    protected function setUp(): void
    {
        // Otras pruebas (las que abren conexión) pueden haber resuelto ya el tenant único.
        $this->previo = TenantContext::resuelto() ? TenantContext::actual() : null;
        TenantContext::olvidar();
    }

    protected function tearDown(): void
    {
        TenantContext::olvidar();
        if ($this->previo !== null) {
            TenantContext::establecer($this->previo);
        }
    }

    public function testSinTenantNoHayConexionPosible(): void
    {
        $this->expectException(TenantNoResuelto::class);
        TenantContext::actual();
    }

    public function testNoCambiaDeInstitucionAMitadDePeticion(): void
    {
        TenantContext::establecer(new Tenant('a', 'sge_a', EstadoTenant::Activo));
        TenantContext::establecer(new Tenant('a', 'sge_a', EstadoTenant::Activo)); // el mismo, sin problema

        $this->expectException(\LogicException::class);
        TenantContext::establecer(new Tenant('b', 'sge_b', EstadoTenant::Activo));
    }

    public function testEstadosQuePuedenEntrar(): void
    {
        $pueden = array_values(array_filter(EstadoTenant::cases(), static fn (EstadoTenant $e): bool => $e->permiteAcceso()));
        self::assertSame([EstadoTenant::Prueba, EstadoTenant::Activo, EstadoTenant::Moroso], $pueden);
    }
}
