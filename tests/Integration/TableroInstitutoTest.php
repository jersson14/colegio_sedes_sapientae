<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Institucion\Configuracion;
use App\Institucion\DocentesPorUnidad;
use App\Institucion\MatriculaPorUnidad;
use App\Institucion\TableroInstituto;

/** Tablero de inicio de un instituto: cifras del semestre en curso y alertas. */
final class TableroInstitutoTest extends BaseDatosTestCase
{
    private function sp(string $sql, array $p): int
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($p);
        $v = (int) $q->fetchColumn();
        $q->closeCursor();
        return $v;
    }

    public function testCifrasDelSemestreEnCurso(): void
    {
        $tablero = new TableroInstituto($this->pdo);
        $periodo = $tablero->periodoActual();
        self::assertNotNull($periodo);
        $p = $periodo['id'];

        $programa = $this->sp('CALL SP_GUARDAR_PROGRAMA(0, ?, ?, ?)', ['TAB-T', 'Programa del tablero', 'ACTIVO']);
        $modulo = $this->sp('CALL SP_GUARDAR_MODULO(0, ?, ?, ?)', [$programa, 'Módulo', 1]);
        $a = $this->sp('CALL SP_GUARDAR_UNIDAD(0, ?, ?, ?, ?, ?, ?, ?, ?)', [$modulo, 'TAB-A', 'Unidad A', 1, '3.0', 32, 32, 'ACTIVO']);
        $b = $this->sp('CALL SP_GUARDAR_UNIDAD(0, ?, ?, ?, ?, ?, ?, ?, ?)', [$modulo, 'TAB-B', 'Unidad B', 1, '2.0', 32, 32, 'ACTIVO']);
        $antes = $tablero->resumen();
        $alumnos = $this->pdo->query("SELECT Id_alumno FROM alumnos ORDER BY Id_alumno LIMIT 2")->fetchAll(\PDO::FETCH_COLUMN);
        $m = new MatriculaPorUnidad($this->pdo);
        $c = new Configuracion(['institucion.tipo' => 'INSTITUTO']);
        foreach ($alumnos as $i => $al) {
            $m->matricular((int) $al, $a, $p);
            $m->matricular((int) $al, $b, $p);
            $id = (int) $this->pdo->query("SELECT id_matricula_unidad FROM matricula_unidades WHERE id_alumno = $al AND id_unidad = $a AND id_periodo = $p")->fetchColumn();
            $m->registrarNota('calificar', $id, $i === 0 ? '15' : '9', $c); // uno aprueba A, el otro no
        }
        (new DocentesPorUnidad($this->pdo))->asignar($a, $p, (int) $this->pdo->query("SELECT MIN(Id_docente) FROM docentes WHERE docente_estatus = 'ACTIVO'")->fetchColumn());

        $r = $tablero->resumen();
        self::assertSame($antes['matriculados'] + 2, $r['matriculados']);
        self::assertSame($antes['por_calificar'] + 2, $r['por_calificar'], 'B sigue en curso para los dos');
        self::assertSame($antes['cargos'] + 1, $r['cargos'], 'el que desaprobó A tiene un cargo');
        self::assertContains('TAB-B', array_column($r['sin_docente'], 'codigo'), 'B no tiene docente');
        self::assertNotContains('TAB-A', array_column($r['sin_docente'], 'codigo'));
        $menor = array_column($r['menor_aprobacion'], 'aprobacion', 'codigo');
        self::assertSame('50.0', (string) $menor['TAB-A']);
        self::assertContains('Programa del tablero', array_column($r['por_programa'], 'programa'));
    }
}
