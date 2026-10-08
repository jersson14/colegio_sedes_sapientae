<?php

declare(strict_types=1);

namespace Tests\Unit\Tarea;

use App\Domain\Tarea\Actividad;
use App\Services\GestionarTareas;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Dobles.php';

final class GestionarTareasTest extends TestCase
{
    private AlmacenDocumentosFalso $almacen;
    private TareaRepositorioFalso $repo;
    private GestionarTareas $servicio;

    protected function setUp(): void
    {
        $this->almacen = new AlmacenDocumentosFalso();
        $this->repo = new TareaRepositorioFalso();
        $this->servicio = new GestionarTareas($this->repo, $this->almacen);
    }

    private const FORM = ['id' => 'T0000001', 'asig' => '20', 'tema' => 'Fracciones', 'fecha' => '2025-12-30', 'descrip' => 'x'];

    public function testAlEntregarSeBorraLaCarpetaAnteriorSoloSiLaBdAcepto(): void
    {
        self::assertTrue($this->servicio->entregar('5', 'controller/tareas/documentos/vieja', reemplazo: true));
        self::assertSame(['controller/tareas/documentos/vieja'], $this->almacen->borradas);

        $this->repo->respuesta = 0; // vencida o calificada
        self::assertFalse($this->servicio->entregar('5', 'controller/tareas/documentos/vieja', reemplazo: true));
        self::assertSame(
            ['controller/tareas/documentos/vieja', 'controller/tareas/documentos/tarea_alumnos_nueva'],
            $this->almacen->borradas,
            'se descarta la nueva, la vieja se conserva'
        );
    }

    public function testSinArchivoNoHayEntrega(): void
    {
        $this->almacen->recibidos = [];
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->entregar('5', '', reemplazo: false);
    }

    public function testModificarSinArchivosConservaLaCarpeta(): void
    {
        $this->almacen->recibidos = [];
        self::assertSame(1, $this->servicio->modificar(self::FORM, 'controller/tareas/documentos/vieja'));
        self::assertSame([], $this->almacen->guardadas);
        self::assertSame([], $this->almacen->borradas);
    }

    public function testUnaPublicacionRechazadaNoDejaCarpetaHuerfana(): void
    {
        $this->repo->respuesta = 2;
        self::assertSame(2, $this->servicio->publicar(self::FORM));
        self::assertSame($this->almacen->guardadas, $this->almacen->borradas);
    }

    public function testEliminarBorraTambienLosArchivos(): void
    {
        self::assertTrue($this->servicio->eliminar('T0000001'));
        self::assertSame(['controller/tareas/documentos/vieja'], $this->almacen->borradas);
    }

    /** @return iterable<string, array{string}> */
    public static function notasInvalidas(): iterable
    {
        yield 'mayor que 20' => ['25'];
        yield 'negativa' => ['-1'];
        yield 'decimal' => ['15.5'];
        yield 'literal' => ['AD'];
    }

    #[DataProvider('notasInvalidas')]
    public function testLaCalificacionEsUnEnteroDe0A20(string $nota): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->calificar('5', $nota, '');
    }

    public function testSoloSeFinalizaYLosEstadosDeExamenSonLosDelEnum(): void
    {
        self::assertTrue($this->servicio->cambiarEstado('T1', 'finalizado'));
        self::assertTrue($this->servicio->estadoExamen('E1', 'realizado'));
        $this->expectException(InvalidArgumentException::class);
        $this->servicio->cambiarEstado('T1', 'PENDIENTE');
    }

    public function testFechasQueEnviaElPanel(): void
    {
        self::assertSame('2025-12-30 00:00:00', Actividad::fecha('2025-12-30'));
        self::assertSame('2025-12-30 10:00:00', Actividad::fecha('2025-12-30T10:00'));
        self::assertSame('2025-12-30 10:00:15', Actividad::fecha('2025-12-30 10:00:15'));
        $this->expectException(InvalidArgumentException::class);
        Actividad::fecha('30/12/2025');
    }
}
