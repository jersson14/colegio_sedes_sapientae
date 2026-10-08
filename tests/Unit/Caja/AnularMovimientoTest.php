<?php

declare(strict_types=1);

namespace Tests\Unit\Caja;

use App\Domain\Caja\Movimiento;
use App\Repositories\CajaRepositorio;
use App\Services\AnularMovimiento;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AnularMovimientoTest extends TestCase
{
    /** @var CajaRepositorio&object{llamadas: list<array{Movimiento, int, string, int}>} */
    private CajaRepositorio $repo;

    protected function setUp(): void
    {
        $this->repo = new class () implements CajaRepositorio {
            /** @var list<array{Movimiento, int, string, int}> */
            public array $llamadas = [];

            public function anular(Movimiento $tipo, int $id, string $motivo, int $anuladoPor): bool
            {
                $this->llamadas[] = [$tipo, $id, $motivo, $anuladoPor];
                return true;
            }
        };
    }

    public function testAnulaConElMotivoNormalizadoYQuienAnula(): void
    {
        self::assertTrue((new AnularMovimiento($this->repo))->ejecutar(Movimiento::Egreso, '7', ' pago <doble> ', 9));
        self::assertSame([[Movimiento::Egreso, 7, 'PAGO &LT;DOBLE&GT;', 9]], $this->repo->llamadas);
    }

    /** @return iterable<string, array{mixed, mixed, int}> */
    public static function invalidos(): iterable
    {
        yield 'sin motivo' => ['7', '   ', 9];
        yield 'motivo de más de 255' => ['7', str_repeat('a', 256), 9];
        yield 'id inválido' => ['abc', 'motivo', 9];
        yield 'sin usuario de sesión' => ['7', 'motivo', 0];
    }

    #[DataProvider('invalidos')]
    public function testRechazaSinTocarLaBd(mixed $id, mixed $motivo, int $usuario): void
    {
        try {
            (new AnularMovimiento($this->repo))->ejecutar(Movimiento::Ingreso, $id, $motivo, $usuario);
            self::fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame([], $this->repo->llamadas);
        }
    }
}
