<?php

declare(strict_types=1);

namespace Tests\Unit\Horario;

use App\Domain\Horario\Clase;
use App\Domain\Horario\ResultadoClase;
use App\Repositories\HorarioRepositorio;
use App\Services\GestionarHorarios;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GestionarHorariosTest extends TestCase
{
    /** @param array{registradas: int, yaEstaban: int, choque: ?ResultadoClase} $respuesta */
    private static function servicio(array $respuesta): GestionarHorarios
    {
        return new GestionarHorarios(new class ($respuesta) implements HorarioRepositorio {
            /** @param array{registradas: int, yaEstaban: int, choque: ?ResultadoClase} $respuesta */
            public function __construct(private readonly array $respuesta)
            {
            }

            public function registrar(array $clases): array
            {
                return $this->respuesta;
            }

            public function eliminarDeAula(int $aula, int $anio): void
            {
            }

            public function registrarAsignatura(string $nombre, int $aula, string $observaciones): bool
            {
                return true;
            }

            public function modificarAsignatura(int $id, string $nombre, int $aula, string $observaciones): bool
            {
                return true;
            }

            public function eliminarAsignatura(int $id): bool
            {
                return true;
            }
        });
    }

    /** @return iterable<string, array{array{registradas: int, yaEstaban: int, choque: ?ResultadoClase}, bool, int}> */
    public static function codigos(): iterable
    {
        yield 'registrar: todas nuevas' => [['registradas' => 3, 'yaEstaban' => 0, 'choque' => null], false, 1];
        yield 'registrar: alguna ya estaba' => [['registradas' => 2, 'yaEstaban' => 1, 'choque' => null], false, 2];
        yield 'editar: se añadió alguna' => [['registradas' => 1, 'yaEstaban' => 4, 'choque' => null], true, 1];
        yield 'editar: ya estaban todas' => [['registradas' => 0, 'yaEstaban' => 5, 'choque' => null], true, 2];
        yield 'celda ocupada' => [['registradas' => 0, 'yaEstaban' => 0, 'choque' => ResultadoClase::CeldaOcupada], false, 3];
        yield 'docente ocupado al editar' => [['registradas' => 0, 'yaEstaban' => 0, 'choque' => ResultadoClase::DocenteOcupado], true, 4];
    }

    /** @param array{registradas: int, yaEstaban: int, choque: ?ResultadoClase} $respuesta */
    #[DataProvider('codigos')]
    public function testCodigosQueEsperaElPanel(array $respuesta, bool $alEditar, int $esperado): void
    {
        $clase = ['idhora' => 41, 'idasig' => 20, 'dia' => 'lunes'];
        self::assertSame($esperado, self::servicio($respuesta)->registrar([$clase], $alEditar));
    }

    public function testElDiaDebeSerDeLunesAViernes(): void
    {
        self::assertSame('MIERCOLES', Clase::desdeArreglo(['idhora' => 1, 'idasig' => 1, 'dia' => 'miercoles'])->dia);
        $this->expectException(InvalidArgumentException::class);
        Clase::desdeArreglo(['idhora' => 1, 'idasig' => 1, 'dia' => 'SABADO']);
    }

    public function testLaAsignaturaExigeNombreYAula(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::servicio(['registradas' => 0, 'yaEstaban' => 0, 'choque' => null])->registrarAsignatura('  ', '5', '');
    }

    public function testEliminarElHorarioExigeElAnio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::servicio(['registradas' => 0, 'yaEstaban' => 0, 'choque' => null])->eliminarDeAula('5', '');
    }
}
