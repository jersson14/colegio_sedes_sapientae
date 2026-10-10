<?php

declare(strict_types=1);

namespace Tests\Unit\Institucion;

use App\Institucion\UnidadDidactica;
use PHPUnit\Framework\TestCase;

final class UnidadDidacticaTest extends TestCase
{
    /** @param array<string, string> $cambios */
    private static function formulario(array $cambios = []): array
    {
        return $cambios + ['id' => '0', 'modulo' => '3', 'codigo' => ' ud-101 ', 'nombre' => 'Ofimática', 'periodo_academico' => '1',
            'creditos' => '2,5', 'horas_teoricas' => '16', 'horas_practicas' => '48', 'estado' => 'ACTIVO'];
    }

    public function testNormalizaElFormulario(): void
    {
        $u = UnidadDidactica::desdeFormulario(self::formulario());
        self::assertSame(['UD-101', '2.5', 3, 16, 48, true], [$u->codigo, $u->creditos, $u->modulo, $u->horasTeoricas, $u->horasPracticas, $u->activa]);
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function invalidas(): array
    {
        return [
            'sin créditos' => [['creditos' => '0'], 'créditos'],
            'créditos con dos decimales' => [['creditos' => '2.25'], 'créditos'],
            'periodo XI' => [['periodo_academico' => '11'], 'periodo'],
            'sin horas' => [['horas_teoricas' => '0', 'horas_practicas' => '0'], 'horas'],
            'código con espacios' => [['codigo' => 'UD 1'], 'código'],
            'sin módulo' => [['modulo' => ''], 'módulo'],
        ];
    }

    /** @param array<string, string> $cambios */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidas')]
    public function testRechazaConElMotivo(array $cambios, string $motivo): void
    {
        $this->expectExceptionMessage($motivo);
        UnidadDidactica::desdeFormulario(self::formulario($cambios));
    }
}
