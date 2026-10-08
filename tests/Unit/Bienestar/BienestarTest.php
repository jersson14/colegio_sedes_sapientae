<?php

declare(strict_types=1);

namespace Tests\Unit\Bienestar;

use App\Domain\Comunicado\Comunicado;
use App\Domain\Salud\Atencion;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BienestarTest extends TestCase
{
    public function testLaAtencionSeNormalizaComoElSistemaHeredado(): void
    {
        $a = Atencion::desdeFormulario(['estu' => '40', 'motivo' => 'dolor de cabeza', 'diagno' => '', 'observa' => '<b>x</b>']);
        self::assertSame([40, 'DOLOR DE CABEZA', '', '&LT;B&GT;X&LT;/B&GT;'], [$a->matricula, $a->motivo, $a->diagnostico, $a->observaciones]);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function atencionesInvalidas(): iterable
    {
        yield 'sin matrícula' => [['estu' => '', 'motivo' => 'x']];
        yield 'sin motivo' => [['estu' => '40', 'motivo' => '  ']];
        yield 'diagnóstico de más de 255' => [['estu' => '40', 'motivo' => 'x', 'diagno' => str_repeat('a', 256)]];
    }

    /** @param array<string, string> $post */
    #[DataProvider('atencionesInvalidas')]
    public function testRechazaAtencionesInvalidas(array $post): void
    {
        $this->expectException(InvalidArgumentException::class);
        Atencion::desdeFormulario($post);
    }

    public function testComunicado(): void
    {
        $c = Comunicado::desdeFormulario(['tipo' => 'por grado', 'grado' => '5', 'titulo' => 'Aviso', 'descripcion' => 'x']);
        self::assertSame(['POR GRADO', 5, 'AVISO', 'ACTIVO'], [$c->tipo, $c->aula, $c->titulo, $c->estado]);
        $this->expectException(InvalidArgumentException::class);
        Comunicado::desdeFormulario(['tipo' => 'PRIVADO', 'grado' => '5', 'titulo' => 'Aviso']);
    }
}
