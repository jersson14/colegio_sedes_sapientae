<?php

declare(strict_types=1);

namespace Tests\Unit\Alumno;

use App\Domain\Alumno\FichaAlumno;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FichaAlumnoTest extends TestCase
{
    /** @return array<string, string> */
    public static function formulario(array $cambios = []): array
    {
        return $cambios + [
            'dni' => '70000099', 'nombre' => 'Ana María', 'apepa' => "D'Angelo", 'apema' => 'Ruiz', 'sexo' => 'femenino',
            'fechanaci' => '2015-03-04', 'telf' => '900000001', 'direc' => 'Av. Lima 1',
            'dnipa' => '40000001', 'nompa' => 'Padre', 'celpa' => '911', 'dnima' => '40000002', 'nomma' => 'Madre', 'celma' => '922',
        ];
    }

    public function testNormalizaComoElSistemaHeredado(): void
    {
        $f = FichaAlumno::desdeFormulario(self::formulario());
        // strtoupper solo convierte ASCII (PHP 8.2), como el código heredado: la «í» queda igual.
        self::assertSame('ANA MARíA', $f->nombres);
        self::assertSame('D&#039;ANGELO', $f->apellidoPaterno);
        self::assertSame('FEMENINO', $f->sexo);
        self::assertSame('MADRE', $f->padres->datosMama);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function invalidos(): iterable
    {
        yield 'DNI de 9 (antes se truncaba)' => [self::formulario(['dni' => '123456789'])];
        yield 'DNI vacío' => [self::formulario(['dni' => ''])];
        yield 'sin nombres' => [self::formulario(['nombre' => '  '])];
        yield 'sexo fuera del ENUM' => [self::formulario(['sexo' => 'X'])];
        yield 'fecha imposible' => [self::formulario(['fechanaci' => '2015-02-30'])];
        yield 'fecha en otro formato' => [self::formulario(['fechanaci' => '04/03/2015'])];
        yield 'celular de 10' => [self::formulario(['telf' => '9000000011'])];
        yield 'DNI de la madre de 9' => [self::formulario(['dnima' => '400000021'])];
    }

    /** @param array<string, string> $formulario */
    #[DataProvider('invalidos')]
    public function testRechazaDatosQueLaBdTruncariaOGuardariaVacios(array $formulario): void
    {
        $this->expectException(InvalidArgumentException::class);
        FichaAlumno::desdeFormulario($formulario);
    }

    public function testElPadreEsOpcional(): void
    {
        $f = FichaAlumno::desdeFormulario(self::formulario(['dnipa' => '', 'nompa' => '', 'celpa' => '']));
        self::assertSame('', $f->padres->dniPapa);
    }
}
