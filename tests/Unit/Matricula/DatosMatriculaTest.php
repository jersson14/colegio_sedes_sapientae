<?php

declare(strict_types=1);

namespace Tests\Unit\Matricula;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\Monto;
use App\Domain\Usuario\Contrasena;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatosMatriculaTest extends TestCase
{
    /** @return array<string, string> */
    public static function formulario(array $cambios = []): array
    {
        return $cambios + [
            'año' => '5', 'aula' => '5', 'admi' => '50', 'nuevo' => '', 'matri' => '150.5',
            'proce' => 'Colegio X', 'pro' => 'Lima', 'depa' => 'Lima',
            'usu' => 'ana.e2e', 'contra' => 'Cl&ve', 'correo' => 'ana@example.com',
        ];
    }

    public function testNormalizaMontosYTextos(): void
    {
        $d = DatosMatricula::desdeFormulario(self::formulario());
        self::assertSame('50.00', $d->admision->valor);
        self::assertSame('0.00', $d->alumnoNuevo->valor, 'vacío cuenta como 0, como hacía la BD');
        self::assertSame('150.50', $d->matricula->valor);
        self::assertSame('COLEGIO X', $d->procedencia);
        self::assertSame(5, $d->anio);
    }

    /** @return iterable<string, array{string}> */
    public static function montosInvalidos(): iterable
    {
        yield 'más de 8 cifras enteras (no cabe en DECIMAL(10,2))' => ['123456789'];
        yield 'negativo' => ['-10'];
        yield 'tres decimales' => ['10.555'];
        yield 'texto' => ['cien'];
        yield 'coma decimal' => ['10,5'];
    }

    #[DataProvider('montosInvalidos')]
    public function testRechazaMontosQueLaColumnaNoGuarda(string $monto): void
    {
        $this->expectException(InvalidArgumentException::class);
        Monto::desde($monto, 'matrícula');
    }

    public function testAdmiteMontosMayoresA999YElMaximoDeLaColumna(): void
    {
        self::assertSame('1500.00', Monto::desde('1500', 'x')->valor, 'antes se recortaba a 999.99');
        self::assertSame('99999999.99', Monto::desde(Monto::MAXIMO, 'x')->valor);
    }

    public function testExigeAnioYAula(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DatosMatricula::desdeFormulario(self::formulario(['aula' => '']));
    }

    public function testRechazaUnaProvinciaMasLargaQueLaColumna(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DatosMatricula::desdeFormulario(self::formulario(['pro' => str_repeat('a', 51)]));
    }

    public function testLaCuentaGuardaElUsuarioEnMayusculasYSoloElHash(): void
    {
        $c = CuentaNueva::desdeFormulario(self::formulario());
        self::assertSame('ANA.E2E', $c->usuario);
        self::assertSame('ANA@EXAMPLE.COM', $c->correo);
        self::assertTrue(Contrasena::verificar('Cl&ve', $c->hashContrasena));
    }
}
