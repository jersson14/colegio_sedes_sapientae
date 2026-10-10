<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\TareasProgramadas;

/**
 * Lo que hacían los eventos de la BD, ahora por cron (tools/tareas_programadas.php).
 * El reloj de MySQL se fija con SET SESSION timestamp.
 */
final class TareasProgramadasTest extends BaseDatosTestCase
{
    private function reloj(string $fecha): void
    {
        $this->pdo->prepare('SET SESSION timestamp = UNIX_TIMESTAMP(?)')->execute([$fecha]);
    }

    private function contar(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->exec('SET SESSION timestamp = DEFAULT');
        }
        parent::tearDown();
    }

    public function testCierraTareasYExamenesVencidosYSoloEsos(): void
    {
        $this->reloj('2026-05-10 12:00:00');
        $this->pdo->exec("UPDATE tareas SET estado = 'PENDIENTE', fecha_entrega = '2026-05-10 11:59:00'");
        $this->pdo->exec("UPDATE tareas SET fecha_entrega = '2026-05-10 12:01:00' ORDER BY id_tarea LIMIT 1");
        $this->pdo->exec("UPDATE examen SET estado = 'PENDIENTE', fecha_examen = '2026-05-09'");
        $tareas = $this->contar('SELECT COUNT(*) FROM tareas');
        $examenes = $this->contar('SELECT COUNT(*) FROM examen');
        if ($tareas < 2 || $examenes < 1) {
            self::markTestSkipped('Los datos de prueba no traen tareas y exámenes suficientes.');
        }

        $cerradas = (new TareasProgramadas($this->pdo))->cerrarVencidas();

        self::assertSame(['tareas' => $tareas - 1, 'examenes' => $examenes], $cerradas);
        self::assertSame(1, $this->contar("SELECT COUNT(*) FROM tareas WHERE estado != 'FINALIZADO'"), 'la que aún no vence sigue abierta');
        self::assertSame(['tareas' => 0, 'examenes' => 0], (new TareasProgramadas($this->pdo))->cerrarVencidas(), 'una segunda pasada no cambia nada');
    }

    public function testElCierreDeAnioOcurreUnaSolaVezTrasEl31DeDiciembre(): void
    {
        $this->pdo->exec("UPDATE alumnos SET alum_estatus = 'SI'");
        $this->pdo->exec('DELETE FROM log_eventos_alumnos');
        $activos = $this->contar("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'");
        $tareas = new TareasProgramadas($this->pdo);

        $this->reloj('2026-12-31 23:58:00');
        self::assertNull($tareas->cierreDeAnio(), 'un minuto antes, nada');

        $this->reloj('2026-12-31 23:59:00');
        self::assertSame($activos, $tareas->cierreDeAnio());
        self::assertSame(0, $this->contar("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'"));
        self::assertSame(1, $this->contar("SELECT COUNT(*) FROM log_eventos_alumnos WHERE anio = 2026 AND alumnos_afectados = $activos"));

        // Se reactivan alumnos en enero (matrícula nueva): el cron que sigue corriendo no los vuelve a cerrar.
        $this->pdo->exec("UPDATE alumnos SET alum_estatus = 'SI'");
        $this->reloj('2027-01-02 08:00:00');
        self::assertNull($tareas->cierreDeAnio());
        self::assertSame($activos, $this->contar("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'"));
    }

    public function testSiElCronEstuvoCaidoSeRecuperaLaPrimeraSemanaYNuncaDespues(): void
    {
        $this->pdo->exec("UPDATE alumnos SET alum_estatus = 'SI'");
        $this->pdo->exec('DELETE FROM log_eventos_alumnos');
        $tareas = new TareasProgramadas($this->pdo);

        // Instalado en octubre: el cierre de diciembre pasado ya no toca (no desactiva a mitad de año).
        $this->reloj('2026-10-10 10:00:00');
        self::assertNull($tareas->cierreDeAnio());
        self::assertSame(0, $this->contar("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'NO'"));

        $this->reloj('2027-01-05 07:00:00');
        self::assertNotNull($tareas->cierreDeAnio(), 'el 5 de enero aún recupera el cierre de 2026');
        self::assertSame(1, $this->contar('SELECT COUNT(*) FROM log_eventos_alumnos WHERE anio = 2026'));
    }

    public function testRespetaElCierreQueYaHizoElEventoDeLaBd(): void
    {
        $this->pdo->exec("UPDATE alumnos SET alum_estatus = 'SI'");
        $this->pdo->exec('DELETE FROM log_eventos_alumnos');
        $this->pdo->prepare('INSERT INTO log_eventos_alumnos (evento, anio, fecha_ejecucion, alumnos_afectados) VALUES (?, 2026, NOW(), 3)')
            ->execute([TareasProgramadas::EVENTO_CIERRE]);

        $this->reloj('2027-01-01 00:00:00');
        self::assertNull((new TareasProgramadas($this->pdo))->cierreDeAnio());
        self::assertSame(0, $this->contar("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'NO'"));
    }
}
