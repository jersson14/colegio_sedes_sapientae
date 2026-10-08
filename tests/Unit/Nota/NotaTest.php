<?php

declare(strict_types=1);

namespace Tests\Unit\Nota;

use App\Domain\Nota\EdicionNota;
use App\Support\Lote;
use App\Domain\Nota\NotaDePadres;
use App\Domain\Nota\RegistroNota;
use App\Domain\Nota\ValorNota;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NotaTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function validas(): iterable
    {
        yield 'cero' => ['0', '0'];
        yield 'veinte' => ['20', '20'];
        yield 'con decimal' => ['14.5', '14.5'];
        yield 'literal en minúsculas' => [' ad ', 'AD'];
        yield 'C' => ['c', 'C'];
    }

    #[DataProvider('validas')]
    public function testAceptaLasEscalasDelColegio(string $entrada, string $esperado): void
    {
        self::assertSame($esperado, ValorNota::desde($entrada)->valor);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidas(): iterable
    {
        yield 'texto' => ['XYZ'];
        yield 'mayor que 20' => ['21'];
        yield 'negativa' => ['-1'];
        yield 'dos decimales' => ['14.55'];
        yield 'vacía' => [''];
        yield 'D (no existe en la escala)' => ['D'];
        yield 'HTML' => ['<b>'];
    }

    #[DataProvider('invalidas')]
    public function testRechazaLoQueNoEsUnaNota(string $entrada): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValorNota::desde($entrada);
    }

    public function testLasConclusionesSeGuardanEscapadas(): void
    {
        $r = RegistroNota::desdeArreglo(['id_matri' => '40', 'perio' => 12, 'cri' => '1', 'nota' => '15', 'conclu' => '<img src=x onerror=alert(1)>']);
        self::assertSame('&lt;img src=x onerror=alert(1)&gt;', $r->conclusiones);
        self::assertSame(['id_matri' => 40, 'perio' => 12, 'cri' => 1, 'nota' => '15', 'conclu' => '&lt;img src=x onerror=alert(1)&gt;'], $r->paraProcedimiento());
    }

    public function testUnaConclusionQueEscapadaNoCabeSeRechaza(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // 64 «&» escapados son 320 caracteres: más que la columna (255).
        EdicionNota::delAlumno(['id_nota_bole' => 1, 'nota' => '10', 'conclusiones' => str_repeat('&', 64)]);
    }

    public function testIdentificadoresInvalidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RegistroNota::desdeArreglo(['id_matri' => '0', 'perio' => 12, 'cri' => 1, 'nota' => '15']);
    }

    public function testLaNotaDePadresExigeCompetencia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NotaDePadres::desdeArreglo(['id_matri' => 40, 'perio' => 12, 'competencia' => '  ', 'nota' => 'A']);
    }

    /** @return iterable<string, array{string}> */
    public static function lotesInvalidos(): iterable
    {
        yield 'vacío' => ['[]'];
        yield 'no es JSON' => ['hola'];
        yield 'objeto, no lista' => ['{"a":1}'];
        yield 'lista de escalares' => ['[1,2]'];
    }

    #[DataProvider('lotesInvalidos')]
    public function testElLoteDebeSerUnaListaDeObjetos(string $json): void
    {
        $this->expectException(InvalidArgumentException::class);
        Lote::desdeJson($json);
    }
}
