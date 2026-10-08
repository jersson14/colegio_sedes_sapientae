<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Tarea\Actividad;
use App\Repositories\PdoTareaRepositorio;

/** PdoTareaRepositorio contra los SP reales (migración 20261020000000 incluida). Fecha: 2025-12-26 12:00. */
final class TareaRepositorioTest extends BaseDatosTestCase
{
    private PdoTareaRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor("SELECT COUNT(*) FROM examen WHERE id_examen = 'D0000001'") === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        $this->repo = new PdoTareaRepositorio($this->pdo);
    }

    private function valor(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    /** Publica una tarea del curso 20 (aula 5) y devuelve [id_tarea, id_detalle_tarea de la matrícula 40]. */
    private function tarea(string $tema, string $fecha): array
    {
        $datos = Actividad::desdeFormulario(['asig' => '20', 'tema' => $tema, 'fecha' => $fecha, 'descrip' => ''], 150);
        self::assertSame(1, $this->repo->publicar($datos, 'controller/tareas/documentos/1700000000'));
        $id = (string) $this->valor("SELECT id_tarea FROM tareas WHERE tema = '$tema'");
        return [$id, (int) $this->valor("SELECT id_detalle_tarea FROM detalle_tarea WHERE id_tarea = '$id' AND id_matriculado = 40")];
    }

    public function testSoloSeEntregaEnPlazoYSinCalificar(): void
    {
        [, $vigente] = $this->tarea('VIGENTE IT', '2025-12-31');
        [, $vencida] = $this->tarea('VENCIDA IT', '2025-12-20');
        self::assertTrue($this->repo->entregar($vigente, 'controller/tareas/documentos/tarea_alumnos_1700000001', false));
        self::assertSame('ENVIADO', $this->valor("SELECT estado FROM detalle_tarea WHERE id_detalle_tarea = $vigente"));
        self::assertFalse($this->repo->entregar($vencida, 'x', false), 'vencida');

        self::assertTrue($this->repo->calificar($vigente, 18, 'BIEN'));
        self::assertFalse($this->repo->entregar($vigente, 'controller/tareas/documentos/tarea_alumnos_1700000002', true), 'ya calificada');
        self::assertSame('CALIFICADO', $this->valor("SELECT estado FROM detalle_tarea WHERE id_detalle_tarea = $vigente"));
        self::assertFalse($this->repo->calificar(99999999, 10, ''), 'entrega inexistente');
    }

    public function testFinalizarCalificaConCincoALosQueNoEntregaron(): void
    {
        [$id, $detalle] = $this->tarea('FINALIZAR IT', '2025-12-31');
        self::assertTrue($this->repo->finalizar($id));
        self::assertSame(['FINALIZADO', 5], [$this->valor("SELECT estado FROM tareas WHERE id_tarea = '$id'"),
            (int) $this->valor("SELECT calificacion FROM detalle_tarea WHERE id_detalle_tarea = $detalle")]);
        $this->pdo->prepare('CALL SP_MODIFICAR_TAREA_ESTATUS(?, ?)')->execute([$id, 'PENDIENTE']);
        self::assertSame('FINALIZADO', $this->valor("SELECT estado FROM tareas WHERE id_tarea = '$id'"), 'otro estado no se aplica');
    }

    public function testUnaTareaConEntregasNoSeElimina(): void
    {
        [$id, $detalle] = $this->tarea('ELIMINAR IT', '2025-12-31');
        $this->repo->entregar($detalle, 'controller/tareas/documentos/tarea_alumnos_1700000003', false);
        self::assertFalse($this->repo->eliminar($id));
        [$sinEntregas] = $this->tarea('ELIMINAR IT 2', '2025-12-31');
        self::assertSame('controller/tareas/documentos/1700000000', $this->repo->carpetaDeTarea($sinEntregas));
        self::assertTrue($this->repo->eliminar($sinEntregas));
    }

    public function testEditarUnExamenConHoraSinCambiarLaFecha(): void
    {
        $examen = static fn (string $tema): Actividad => Actividad::desdeFormulario(['asig' => '47', 'tema' => $tema, 'fecha' => '2025-12-29 09:30:30', 'descrip' => ''], 255);
        self::assertSame(1, $this->repo->modificarExamen('D0000001', $examen('NUEVO TEMA')), 'antes respondía «ya existe»');
        self::assertSame('NUEVO TEMA', $this->valor("SELECT tema_examen FROM examen WHERE id_examen = 'D0000001'"));
        self::assertSame(0, $this->repo->modificarExamen('NOEXISTE', $examen('X')));
        self::assertFalse($this->repo->estadoExamen('D0000001', 'CUALQUIERA'));
    }

    public function testElPrimerExamenEmpiezaPorE(): void
    {
        $this->pdo->exec('DELETE FROM examen');
        $examen = Actividad::desdeFormulario(['asig' => '20', 'tema' => 'PRIMERO', 'fecha' => '2026-01-10T08:00', 'descrip' => ''], 255);
        self::assertSame(1, $this->repo->registrarExamen($examen));
        self::assertSame('E0000001', $this->valor('SELECT id_examen FROM examen'));
    }
}
