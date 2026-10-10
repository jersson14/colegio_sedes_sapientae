<?php

declare(strict_types=1);

namespace Tests\Unit\Institucion;

use App\Institucion\Configuracion;
use App\Institucion\Evaluacion\PromedioPorCreditos;
use App\Institucion\Evaluacion\PromedioSimple;
use App\Institucion\Evaluacion\RecordAcademico;
use PHPUnit\Framework\TestCase;

final class EstrategiaEvaluacionTest extends TestCase
{
    private const NOTAS = [['nota' => 18.0, 'creditos' => 4.0], ['nota' => 10.0, 'creditos' => 1.0], ['nota' => 14.0, 'creditos' => 3.0]];

    public function testSimpleTodasPesanIgual(): void
    {
        self::assertSame(14.0, (new PromedioSimple())->promedio(self::NOTAS));
    }

    public function testPonderadoPorCreditos(): void
    {
        // (18×4 + 10×1 + 14×3) / 8 = 124 / 8
        self::assertSame(15.5, (new PromedioPorCreditos())->promedio(self::NOTAS));
    }

    public function testSinNotasNoHayPromedio(): void
    {
        self::assertNull((new PromedioSimple())->promedio([]));
        self::assertNull((new PromedioPorCreditos())->promedio([]));
    }

    public function testLaInstitucionEligeLaEstrategia(): void
    {
        self::assertInstanceOf(PromedioSimple::class, RecordAcademico::estrategia(new Configuracion()));
        self::assertInstanceOf(PromedioPorCreditos::class, RecordAcademico::estrategia(new Configuracion(['institucion.tipo' => 'INSTITUTO'])));
    }

    public function testRangoDeRecuperacion(): void
    {
        self::assertSame(10, (new Configuracion(['institucion.tipo' => 'INSTITUTO']))->recuperacionDesde());
        self::assertSame(11, (new Configuracion())->recuperacionDesde(), 'un colegio: igual a la mínima, sin recuperación');
        self::assertSame(
            12,
            (new Configuracion(['evaluacion.nota_minima' => '12', 'evaluacion.recuperacion_desde' => '15']))->recuperacionDesde(),
            'nunca por encima de la nota mínima'
        );
    }
}
