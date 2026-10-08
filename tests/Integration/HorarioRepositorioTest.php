<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Horario\Clase;
use App\Domain\Horario\ResultadoClase;
use App\Repositories\PdoHorarioRepositorio;

/**
 * PdoHorarioRepositorio contra los SP reales (migración 20261018000000 incluida).
 * El aula 5 (año 5) tiene toda la semana ocupada por cursos del docente 1; la hora 41 es 08:00–09:00.
 */
final class HorarioRepositorioTest extends BaseDatosTestCase
{
    private PdoHorarioRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor('SELECT COUNT(*) FROM horarios WHERE id_hora_aula = 41') === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->repo = new PdoHorarioRepositorio($this->pdo);
    }

    private function valor(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    private function clase(int $hora, int $curso, string $dia): Clase
    {
        return Clase::desdeArreglo(['idhora' => $hora, 'idasig' => $curso, 'dia' => $dia]);
    }

    /** Un curso del docente 1 en el aula 15 y una hora de esa aula (año 5) entre $inicio y $fin. */
    private function cursoDelDocente1EnOtraAula(string $inicio, string $fin): array
    {
        $this->pdo->exec("INSERT INTO asignaturas (nombre_asig, Id_grado, estado) VALUES ('CURSO PRUEBA', 15, 'ACTIVO')");
        $asignatura = (int) $this->pdo->lastInsertId();
        $docente1 = (int) $this->valor('SELECT Id_asig_docente FROM detalle_asignatura_docente WHERE Id_detalle_asig_docente = 20');
        $this->pdo->exec("INSERT INTO detalle_asignatura_docente (Id_asig_docente, Id_asignatura) VALUES ($docente1, $asignatura)");
        $curso = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO horas_aula (`id_año_academico`, id_aula, turno, hora_inicio, hora_fin, estado)
            VALUES (5, 15, 'TARDE', '$inicio', '$fin', 'ACTIVO')");
        return [(int) $this->pdo->lastInsertId(), $curso];
    }

    public function testUnaCeldaOcupadaPorOtroCursoNoAdmiteUnSegundo(): void
    {
        $r = $this->repo->registrar([$this->clase(41, 21, 'LUNES')]);
        self::assertSame(ResultadoClase::CeldaOcupada, $r['choque']);
        self::assertSame(1, (int) $this->valor("SELECT COUNT(*) FROM horarios WHERE id_hora_aula = 41 AND dia = 'LUNES'"));
    }

    public function testElMismoDocenteNoPuedeEstarEnDosAulasALaVez(): void
    {
        [$hora, $curso] = $this->cursoDelDocente1EnOtraAula('08:30:00', '09:30:00'); // se solapa con 08:00–09:00
        self::assertSame(ResultadoClase::DocenteOcupado, $this->repo->registrar([$this->clase($hora, $curso, 'LUNES')])['choque']);
        [$libre, $curso2] = $this->cursoDelDocente1EnOtraAula('13:30:00', '14:30:00'); // el aula 5 termina a las 13:00
        self::assertSame(['registradas' => 1, 'yaEstaban' => 0, 'choque' => null], $this->repo->registrar([$this->clase($libre, $curso2, 'LUNES')]));
    }

    public function testUnChoqueImpideRegistrarElRestoDelLote(): void
    {
        $this->pdo->exec("INSERT INTO horas_aula (`id_año_academico`, id_aula, turno, hora_inicio, hora_fin, estado)
            VALUES (5, 5, 'TARDE', '15:00:00', '16:00:00', 'ACTIVO')");
        $libre = (int) $this->pdo->lastInsertId();
        // Dentro de la transacción de la prueba: el punto de guardado deshace la primera clase.
        $r = $this->repo->registrar([$this->clase($libre, 21, 'LUNES'), $this->clase(41, 21, 'LUNES')]);
        self::assertSame(ResultadoClase::CeldaOcupada, $r['choque']);
        self::assertSame(0, (int) $this->valor("SELECT COUNT(*) FROM horarios WHERE id_hora_aula = $libre"), 'la primera clase se deshizo');
        self::assertTrue($this->pdo->inTransaction(), 'la transacción de quien llama sigue abierta');
    }

    public function testEliminarBorraSoloElAnioIndicado(): void
    {
        $this->pdo->exec("INSERT INTO horas_aula (`id_año_academico`, id_aula, turno, hora_inicio, hora_fin, estado)
            VALUES (2, 5, 'MAÑANA', '08:00:00', '09:00:00', 'ACTIVO')");
        $horaAnterior = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO horarios (id_hora_aula, id_detalle_asig_docente, dia, estado) VALUES ($horaAnterior, 20, 'LUNES', 'ACTIVO')");

        $this->repo->eliminarDeAula(5, 5);
        self::assertSame(0, (int) $this->valor('SELECT COUNT(*) FROM horarios h JOIN horas_aula ha ON ha.id_hora = h.id_hora_aula
            WHERE ha.id_aula = 5 AND ha.`id_año_academico` = 5'));
        self::assertSame(1, (int) $this->valor("SELECT COUNT(*) FROM horarios WHERE id_hora_aula = $horaAnterior"), 'el año anterior se conserva');
    }

    public function testAsignaturaEnUsoNoSeEliminaYDuplicadaNoSeRegistra(): void
    {
        self::assertFalse($this->repo->eliminarAsignatura(2), 'tiene docente asignado: false, no un error de clave foránea');
        self::assertTrue($this->repo->registrarAsignatura('TALLER IT', 5, ''));
        self::assertFalse($this->repo->registrarAsignatura('TALLER IT', 5, ''));
        $id = (int) $this->valor("SELECT Id_asignatura FROM asignaturas WHERE nombre_asig = 'TALLER IT'");
        self::assertTrue($this->repo->eliminarAsignatura($id));
    }
}
