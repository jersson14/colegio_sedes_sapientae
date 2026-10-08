<?php

declare(strict_types=1);

namespace Tests\Unit\Alumno;

use App\Domain\Alumno\FichaAlumno;
use App\Services\GestionarAlumnos;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Dobles.php';

final class GestionarAlumnosTest extends TestCase
{
    private AlumnoRepositorioEnMemoria $repo;
    private FotosEnMemoria $fotos;
    private GestionarAlumnos $servicio;

    protected function setUp(): void
    {
        $this->repo = new AlumnoRepositorioEnMemoria();
        $this->fotos = new FotosEnMemoria();
        $this->servicio = new GestionarAlumnos($this->repo, $this->fotos);
    }

    private static function ficha(string $dni): FichaAlumno
    {
        return FichaAlumno::desdeFormulario(FichaAlumnoTest::formulario(['dni' => $dni]));
    }

    public function testRegistraSinFotoConLaMarcaDeSinFoto(): void
    {
        self::assertTrue($this->servicio->registrar(self::ficha('70000001'), false));
        self::assertSame('fotos/', $this->repo->fotoPorDni('70000001'));
        self::assertSame(0, $this->fotos->validadas);
        self::assertSame([], $this->fotos->guardadas);
    }

    public function testRegistraConFotoYLaGuardaDespuesDeLaBd(): void
    {
        self::assertTrue($this->servicio->registrar(self::ficha('70000001'), true));
        self::assertSame(['fotos/NUEVA1.png'], $this->fotos->guardadas);
        self::assertSame('fotos/NUEVA1.png', $this->repo->fotoPorDni('70000001'));
    }

    public function testUnDniRepetidoNoGuardaLaFoto(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), false);
        self::assertFalse($this->servicio->registrar(self::ficha('70000001'), true));
        self::assertSame([], $this->fotos->guardadas, 'la foto se validó pero no se mueve si la BD no aceptó');
    }

    public function testModificarSinFotoConservaLaDeLaBdNoLaDelFormulario(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), true);
        self::assertTrue($this->servicio->modificar(1, self::ficha('70000001'), false));
        self::assertSame('fotos/NUEVA1.png', $this->repo->fotoPorId(1));
        self::assertSame([], $this->fotos->borradas);
    }

    public function testModificarConFotoNuevaBorraLaAnteriorSoloSiSeGuardo(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), true);
        $this->servicio->modificar(1, self::ficha('70000001'), true);
        self::assertSame(['fotos/NUEVA1.png'], $this->fotos->borradas);

        $this->fotos->fallarAlGuardar = true;
        $this->servicio->modificar(1, self::ficha('70000001'), true);
        self::assertSame(['fotos/NUEVA1.png'], $this->fotos->borradas, 'si no se pudo guardar la nueva, la anterior no se borra');
    }

    public function testNoModificaConElDniDeOtroAlumno(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), false);
        $this->servicio->registrar(self::ficha('70000002'), false);
        self::assertFalse($this->servicio->modificar(2, self::ficha('70000001'), true));
        self::assertSame([], $this->fotos->guardadas);
    }

    public function testEliminarBorraTambienSuFoto(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), true);
        self::assertTrue($this->servicio->eliminar('70000001'));
        self::assertSame(['fotos/NUEVA1.png'], $this->fotos->borradas);
    }

    public function testUnAlumnoMatriculadoNoSeEliminaNiPierdeSuFoto(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), true);
        $this->repo->matriculados[] = '70000001';
        self::assertFalse($this->servicio->eliminar('70000001'));
        self::assertSame([], $this->fotos->borradas);
        self::assertFalse($this->servicio->eliminar('79999999'), 'inexistente');
    }

    public function testCambiarFotoYQuitarla(): void
    {
        $this->servicio->registrar(self::ficha('70000001'), false);
        $this->servicio->cambiarFoto('70000001', true);
        self::assertSame('fotos/NUEVA1.png', $this->repo->fotoPorDni('70000001'));
        self::assertSame(['fotos/'], $this->fotos->borradas, 'la marca sin foto se «borra» sin efecto (no es un archivo)');

        $this->servicio->cambiarFoto('70000001', false);
        self::assertSame('fotos/', $this->repo->fotoPorDni('70000001'));
        self::assertSame(['fotos/', 'fotos/NUEVA1.png'], $this->fotos->borradas);
    }
}
