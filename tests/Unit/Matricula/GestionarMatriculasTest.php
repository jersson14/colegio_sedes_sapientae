<?php

declare(strict_types=1);

namespace Tests\Unit\Matricula;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;
use App\Repositories\MatriculaRepositorio;
use App\Services\GestionarMatriculas;
use PHPUnit\Framework\TestCase;

final class GestionarMatriculasTest extends TestCase
{
    /** @var MatriculaRepositorio&object{llamadas: list<array{string, int}>} */
    private MatriculaRepositorio $repo;
    private GestionarMatriculas $servicio;

    protected function setUp(): void
    {
        $this->repo = new class () implements MatriculaRepositorio {
            /** @var list<array{string, int}> */
            public array $llamadas = [];

            public function registrar(int $alumno, DatosMatricula $datos, CuentaNueva $cuenta, int $cobrador): ResultadoRegistro
            {
                $this->llamadas[] = ['registrar', $cobrador];
                return ResultadoRegistro::Registrada;
            }

            public function modificar(int $id, DatosMatricula $datos): int
            {
                $this->llamadas[] = ['modificar', $id];
                return 1;
            }

            public function eliminar(int $id): int
            {
                $this->llamadas[] = ['eliminar', $id];
                return 1;
            }
        };
        $this->servicio = new GestionarMatriculas($this->repo);
    }

    private static function datos(): DatosMatricula
    {
        return DatosMatricula::desdeFormulario(DatosMatriculaTest::formulario());
    }

    public function testRegistraANombreDeQuienCobra(): void
    {
        $cuenta = CuentaNueva::desdeFormulario(DatosMatriculaTest::formulario());
        self::assertSame(ResultadoRegistro::Registrada, $this->servicio->registrar(20, self::datos(), $cuenta, 9));
        self::assertSame([['registrar', 9]], $this->repo->llamadas);
    }

    public function testSinAlumnoOSinCobradorNoLlegaALaBd(): void
    {
        $cuenta = CuentaNueva::desdeFormulario(DatosMatriculaTest::formulario());
        self::assertSame(ResultadoRegistro::Invalida, $this->servicio->registrar(0, self::datos(), $cuenta, 9));
        self::assertSame(ResultadoRegistro::Invalida, $this->servicio->registrar(20, self::datos(), $cuenta, 0));
        self::assertSame(0, $this->servicio->modificar(0, self::datos()));
        self::assertSame(0, $this->servicio->eliminar(-1));
        self::assertSame([], $this->repo->llamadas);
    }
}
