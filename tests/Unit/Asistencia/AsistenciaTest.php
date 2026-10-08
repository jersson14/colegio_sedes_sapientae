<?php

declare(strict_types=1);

namespace Tests\Unit\Asistencia;

use App\Domain\Asistencia\Asistencia;
use App\Domain\Asistencia\EstadoAsistencia;
use App\Repositories\AsistenciaRepositorio;
use App\Services\GestionarAsistencia;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AsistenciaTest extends TestCase
{
    public function testNuevaNormalizaElEstadoYEscapaLaObservacion(): void
    {
        $a = Asistencia::nueva(['id_matri' => '40', 'fecha' => '2025-12-01', 'esta' => 'tarde', 'obse' => '<img src=x>']);
        self::assertSame(40, $a->id);
        self::assertSame(EstadoAsistencia::Tarde, $a->estado);
        self::assertSame('&lt;img src=x&gt;', $a->observacion);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function invalidas(): iterable
    {
        $base = ['id_matri' => '40', 'fecha' => '2025-12-01', 'esta' => 'PRESENTE', 'obse' => ''];
        yield 'estado fuera del ENUM (antes se guardaba vacío)' => [['esta' => 'ASISTIO'] + $base];
        yield 'fecha imposible' => [['fecha' => '2025-02-30'] + $base];
        yield 'fecha con hora' => [['fecha' => '2025-12-01 08:00'] + $base];
        yield 'sin matrícula' => [['id_matri' => ''] + $base];
        yield 'observación de más de 1000' => [['obse' => str_repeat('a', 1001)] + $base];
    }

    /** @param array<string, string> $r */
    #[DataProvider('invalidas')]
    public function testRechazaLoQueLaBdGuardariaMal(array $r): void
    {
        $this->expectException(InvalidArgumentException::class);
        Asistencia::nueva($r);
    }

    public function testElServicioValidaTodoAntesDeTocarLaBd(): void
    {
        $repo = new class () implements AsistenciaRepositorio {
            public int $llamadas = 0;

            public function registrar(array $asistencias): int
            {
                $this->llamadas++;
                return 0;
            }

            public function editar(Asistencia $asistencia): bool
            {
                $this->llamadas++;
                return $asistencia->id !== 404;
            }

            public function eliminarDelDia(string $fecha, int $aula): void
            {
                $this->llamadas++;
            }
        };
        $servicio = new GestionarAsistencia($repo);

        try {
            $servicio->editar([['id_asis' => 1, 'esta' => 'PRESENTE'], ['id_asis' => 2, 'esta' => 'X']]);
            self::fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $repo->llamadas, 'ninguna se edita si una es inválida');
        }
        self::assertSame(2, $servicio->editar([['id_asis' => 1, 'esta' => 'PRESENTE'], ['id_asis' => 404, 'esta' => 'AUSENTE']]));
        self::assertSame(1, $servicio->registrar([['id_matri' => 40, 'fecha' => '2025-12-01', 'esta' => 'PRESENTE']]));

        $this->expectException(InvalidArgumentException::class);
        $servicio->eliminarDelDia('ayer', '5');
    }
}
