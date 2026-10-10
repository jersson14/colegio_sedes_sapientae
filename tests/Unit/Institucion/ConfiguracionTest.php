<?php

declare(strict_types=1);

namespace Tests\Unit\Institucion;

use App\Institucion\Configuracion;
use App\Institucion\TipoInstitucion;
use PHPUnit\Framework\TestCase;

final class ConfiguracionTest extends TestCase
{
    public function testSinNadaGuardadoEsUnColegioComoHastaAhora(): void
    {
        $c = new Configuracion();
        self::assertSame(TipoInstitucion::Colegio, $c->tipo());
        self::assertSame(['BIMESTRE', 4, 'Bimestre', 11], [$c->tipoPeriodo(), $c->cantidadPeriodos(), $c->etiquetaPeriodo(), $c->notaMinima()]);
        self::assertTrue($c->apoderadoObligatorio());
        self::assertFalse($c->ponderaPorCreditos());
        self::assertFalse($c->matriculaPorUnidad());
    }

    public function testUnInstitutoTraeSusPropiosValoresPorDefecto(): void
    {
        $c = new Configuracion(['institucion.tipo' => 'INSTITUTO']);
        self::assertSame(['SEMESTRE', 2, 'Semestre', 13], [$c->tipoPeriodo(), $c->cantidadPeriodos(), $c->etiquetaPeriodo(), $c->notaMinima()]);
        self::assertFalse($c->apoderadoObligatorio());
        self::assertTrue($c->ponderaPorCreditos());
        self::assertTrue($c->matriculaPorUnidad());
        self::assertTrue($c->esPorDefecto('periodo.tipo'));
    }

    public function testLoGuardadoMandaSobreElValorPorDefecto(): void
    {
        $c = new Configuracion(['institucion.tipo' => 'INSTITUTO', 'evaluacion.nota_minima' => '12', 'apoderado.obligatorio' => '1']);
        self::assertSame(12, $c->notaMinima());
        self::assertTrue($c->apoderadoObligatorio());
        self::assertFalse($c->esPorDefecto('evaluacion.nota_minima'));
    }

    public function testUnValorCorruptoEnLaTablaNoImpideEntrar(): void
    {
        $c = new Configuracion(['institucion.tipo' => 'UNIVERSIDAD', 'evaluacion.nota_minima' => 'veinte', 'clave.rara' => 'x']);
        self::assertSame(TipoInstitucion::Colegio, $c->tipo());
        self::assertSame(11, $c->notaMinima());
    }

    public function testCuatrimestresSonTresPorAnio(): void
    {
        self::assertSame(3, (new Configuracion(['periodo.tipo' => 'CUATRIMESTRE']))->cantidadPeriodos());
    }

    public function testNormalizaAlGuardar(): void
    {
        self::assertSame('INSTITUTO', Configuracion::validar('institucion.tipo', ' instituto '));
        self::assertSame('1', Configuracion::validar('apoderado.obligatorio', 'sí'));
        self::assertSame('0', Configuracion::validar('apoderado.obligatorio', 'no'));
        self::assertSame('13', Configuracion::validar('evaluacion.nota_minima', '013'));
        self::assertNull(Configuracion::validar('periodo.tipo', ''), 'vacío = volver al valor por defecto');
    }

    /** @return array<string, array{string, string}> */
    public static function invalidos(): array
    {
        return [
            'clave desconocida' => ['otra.cosa', '1'],
            'nota fuera de escala' => ['evaluacion.nota_minima', '21'],
            'nota no numérica' => ['evaluacion.nota_minima', '13.5'],
            'periodo inexistente' => ['periodo.tipo', 'QUINQUEMESTRE'],
            'booleano raro' => ['apoderado.obligatorio', 'tal vez'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidos')]
    public function testRechazaConMotivo(string $clave, string $valor): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Configuracion::validar($clave, $valor);
    }

    public function testLoQueRecibeElJavaScript(): void
    {
        self::assertSame(
            ['tipo' => 'CETPRO', 'tipoPeriodo' => 'SEMESTRE', 'etiquetaPeriodo' => 'Semestre', 'cantidadPeriodos' => 2,
                'notaMinima' => 13, 'apoderadoObligatorio' => false, 'matriculaPorUnidad' => true],
            (new Configuracion(['institucion.tipo' => 'CETPRO']))->paraInterfaz(),
        );
    }
}
