<?php

declare(strict_types=1);

namespace Tests\Unit\Comercial;

use App\Comercial\ModuloOpcional;
use App\Comercial\ModulosInstitucion;
use PHPUnit\Framework\TestCase;

final class ModulosInstitucionTest extends TestCase
{
    public function testDesdeLaListaDeColegioEnv(): void
    {
        $m = ModulosInstitucion::desdeLista(' Facturacion_Electronica , asistente_ia, otro ', 200);
        self::assertTrue($m->activo(ModuloOpcional::FacturacionElectronica));
        self::assertTrue($m->activo(ModuloOpcional::AsistenteIa));
        self::assertSame(200, $m->limite(ModuloOpcional::AsistenteIa));
        self::assertNull($m->limite(ModuloOpcional::FacturacionElectronica), 'la facturación no tiene límite');
        self::assertSame(['facturacion_electronica' => true, 'asistente_ia' => true], $m->paraInterfaz());
    }

    public function testSinNadaNoHayModulos(): void
    {
        $m = ModulosInstitucion::desdeLista('');
        self::assertFalse($m->activo(ModuloOpcional::AsistenteIa));
        self::assertSame(['facturacion_electronica' => false, 'asistente_ia' => false], $m->paraInterfaz());
    }
}
