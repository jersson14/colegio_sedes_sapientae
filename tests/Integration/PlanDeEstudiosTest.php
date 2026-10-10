<?php

declare(strict_types=1);

namespace Tests\Integration;

/** Procedimientos del plan de estudios y de la matrícula por unidad (migración 20261029000000, Fase 5.3 y 5.4). */
final class PlanDeEstudiosTest extends BaseDatosTestCase
{
    private function sp(string $sql, array $parametros): int
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($parametros);
        $valor = (int) $q->fetchColumn();
        $q->closeCursor();
        return $valor;
    }

    /** @return array{programa: int, modulo: int, u1: int, u2: int, u3: int, otro: int} */
    private function plan(): array
    {
        $programa = $this->sp('CALL SP_GUARDAR_PROGRAMA(0, ?, ?, ?)', ['PRG-T', 'Computación e Informática', 'ACTIVO']);
        $modulo = $this->sp('CALL SP_GUARDAR_MODULO(0, ?, ?, ?)', [$programa, 'Gestión de soporte técnico', 1]);
        $unidad = fn (string $codigo, int $periodo): int => $this->sp(
            'CALL SP_GUARDAR_UNIDAD(0, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$modulo, $codigo, "Unidad $codigo", $periodo, '3.0', 32, 32, 'ACTIVO']
        );
        $otroPrograma = $this->sp('CALL SP_GUARDAR_PROGRAMA(0, ?, ?, ?)', ['PRG-O', 'Contabilidad', 'ACTIVO']);
        $otroModulo = $this->sp('CALL SP_GUARDAR_MODULO(0, ?, ?, ?)', [$otroPrograma, 'Contabilidad básica', 1]);
        $otra = $this->sp('CALL SP_GUARDAR_UNIDAD(0, ?, ?, ?, ?, ?, ?, ?, ?)', [$otroModulo, 'UD-O1', 'Otra', 1, '2.0', 16, 16, 'ACTIVO']);
        return ['programa' => $programa, 'modulo' => $modulo, 'u1' => $unidad('UD-T1', 1), 'u2' => $unidad('UD-T2', 2), 'u3' => $unidad('UD-T3', 3), 'otro' => $otra];
    }

    private function periodo(): int
    {
        return (int) $this->pdo->query('SELECT MIN(id_periodo) FROM periodos')->fetchColumn();
    }

    private function alumno(): int
    {
        return (int) $this->pdo->query('SELECT MIN(Id_alumno) FROM alumnos')->fetchColumn();
    }

    public function testCodigosUnicos(): void
    {
        $p = $this->plan();
        self::assertSame(0, $this->sp('CALL SP_GUARDAR_PROGRAMA(0, ?, ?, ?)', ['PRG-T', 'Repetido', 'ACTIVO']));
        self::assertSame($p['programa'], $this->sp('CALL SP_GUARDAR_PROGRAMA(?, ?, ?, ?)', [$p['programa'], 'PRG-T', 'Renombrado', 'ACTIVO']), 'el suyo propio sí');
        self::assertSame(0, $this->sp('CALL SP_GUARDAR_UNIDAD(0, ?, ?, ?, ?, ?, ?, ?, ?)', [$p['modulo'], 'UD-T1', 'Repetida', 1, '1.0', 0, 0, 'ACTIVO']));
    }

    public function testPrerrequisitosNiDeSiMismaNiDeOtroPrograma(): void
    {
        $p = $this->plan();
        self::assertSame(1, $this->sp('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$p['u2'], $p['u1']]));
        self::assertSame(1, $this->sp('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$p['u3'], $p['u2']]));
        // Los ciclos los descarta el servicio (testElServicioDescartaLosCiclosIndirectos): ver la migración.
        self::assertSame(0, $this->sp('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$p['u1'], $p['u1']]), 'de sí misma');
        self::assertSame(0, $this->sp('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$p['u2'], $p['otro']]), 'de otro programa');
    }

    public function testLaMatriculaExigeLosPrerrequisitosAprobados(): void
    {
        $p = $this->plan();
        $this->sp('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$p['u2'], $p['u1']]);
        [$alumno, $periodo] = [$this->alumno(), $this->periodo()];

        self::assertSame(4, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u2'], $periodo]), 'sin u1 aprobada');
        self::assertSame(1, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u1'], $periodo]));
        self::assertSame(2, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u1'], $periodo]), 'dos veces en el mismo periodo');
        self::assertSame(4, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u2'], $periodo]), 'matriculado no es aprobado');

        $this->pdo->prepare("UPDATE matricula_unidades SET estado = 'APROBADO', nota_final = 15 WHERE id_alumno = ? AND id_unidad = ?")->execute([$alumno, $p['u1']]);
        self::assertSame(1, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u2'], $periodo]), 'con u1 aprobada');
        $otroPeriodo = (int) $this->pdo->query("SELECT MAX(id_periodo) FROM periodos")->fetchColumn();
        self::assertSame(3, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u1'], $otroPeriodo]), 'no se vuelve a llevar una unidad aprobada');
        self::assertSame(0, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, 999999, $periodo]));
    }

    public function testRetirarSoloMientrasEstaMatriculado(): void
    {
        $p = $this->plan();
        [$alumno, $periodo] = [$this->alumno(), $this->periodo()];
        $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u1'], $periodo]);
        $id = (int) $this->pdo->query("SELECT MAX(id_matricula_unidad) FROM matricula_unidades")->fetchColumn();
        self::assertSame(1, $this->sp('CALL SP_RETIRAR_UNIDAD(?)', [$id]));
        self::assertSame(0, $this->sp('CALL SP_RETIRAR_UNIDAD(?)', [$id]), 'ya retirado');
        self::assertSame(1, $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$alumno, $p['u1'], $periodo]), 'puede volver en el mismo periodo');
        self::assertSame('MATRICULADO', $this->pdo->query("SELECT estado FROM matricula_unidades WHERE id_matricula_unidad = $id")->fetchColumn(), 'la misma fila');
    }

    public function testNoSeBorraLoQueTieneHistorial(): void
    {
        $p = $this->plan();
        $this->sp('CALL SP_MATRICULAR_UNIDAD(?, ?, ?)', [$this->alumno(), $p['u1'], $this->periodo()]);
        self::assertSame(0, $this->sp('CALL SP_ELIMINAR_UNIDAD(?)', [$p['u1']]), 'con matrículas');
        self::assertSame(1, $this->sp('CALL SP_ELIMINAR_UNIDAD(?)', [$p['u3']]), 'sin matrículas');
        self::assertSame(0, $this->sp('CALL SP_ELIMINAR_MODULO(?)', [$p['modulo']]), 'con unidades');
        self::assertSame(0, $this->sp('CALL SP_ELIMINAR_PROGRAMA(?)', [$p['programa']]), 'con módulos');
    }

    public function testElServicioDescartaLosCiclosIndirectos(): void
    {
        $p = $this->plan();
        $plan = new \App\Institucion\PlanDeEstudios($this->pdo);
        $plan->agregarPrerrequisito($p['u2'], $p['u1']);
        $plan->agregarPrerrequisito($p['u3'], $p['u2']);
        try {
            $plan->agregarPrerrequisito($p['u1'], $p['u3']);
            self::fail('u1 → u3 → u2 → u1 es un ciclo');
        } catch (\DomainException $e) {
            self::assertStringContainsString('ciclo', $e->getMessage());
        }
        $this->expectExceptionMessage('mismo programa');
        $plan->agregarPrerrequisito($p['u1'], $p['otro']);
    }

    public function testSituacionDelAlumno(): void
    {
        $p = $this->plan();
        $matricula = new \App\Institucion\MatriculaPorUnidad($this->pdo);
        (new \App\Institucion\PlanDeEstudios($this->pdo))->agregarPrerrequisito($p['u2'], $p['u1']);
        [$alumno, $periodo] = [$this->alumno(), $this->periodo()];
        $matricula->matricular($alumno, $p['u1'], $periodo);
        $situacion = array_column($matricula->situacion($alumno, $p['programa'], $periodo), 'situacion', 'codigo');
        self::assertSame(['UD-T1' => 'MATRICULADO', 'UD-T2' => 'FALTA_REQUISITO', 'UD-T3' => 'DISPONIBLE'], $situacion);
    }

    public function testCalificacionRecuperacionYRecord(): void
    {
        $p = $this->plan();
        $c = new \App\Institucion\Configuracion(['institucion.tipo' => 'INSTITUTO']); // mínima 13, recuperación desde 10, por créditos
        $matricula = new \App\Institucion\MatriculaPorUnidad($this->pdo);
        [$alumno, $periodo] = [$this->alumno(), $this->periodo()];
        $id = function (int $unidad) use ($alumno): int {
            return (int) $this->pdo->query("SELECT MAX(id_matricula_unidad) FROM matricula_unidades WHERE id_alumno = $alumno AND id_unidad = $unidad")->fetchColumn();
        };
        foreach (['u1', 'u2', 'u3'] as $u) {
            $matricula->matricular($alumno, $p[$u], $periodo);
        }
        self::assertSame(2, $matricula->registrarNota('calificar', $id($p['u1']), '21', $c), 'fuera de la escala');
        self::assertSame(1, $matricula->registrarNota('calificar', $id($p['u1']), '12', $c));
        self::assertSame(0, $matricula->registrarNota('calificar', $id($p['u1']), '15', $c), 'ya calificada');
        self::assertSame(1, $matricula->registrarNota('calificar', $id($p['u2']), '9', $c));
        self::assertSame(1, $matricula->registrarNota('calificar', $id($p['u3']), '17.5', $c));

        self::assertSame(0, $matricula->registrarNota('recuperar', $id($p['u2']), '15', $c), '9 está fuera del rango de recuperación (10–12)');
        self::assertSame(0, $matricula->registrarNota('recuperar', $id($p['u3']), '15', $c), 'una aprobada no se recupera');
        self::assertSame(1, $matricula->registrarNota('recuperar', $id($p['u1']), '14', $c));
        self::assertSame(0, $matricula->registrarNota('recuperar', $id($p['u1']), '16', $c), 'una sola recuperación');

        $record = (new \App\Institucion\Evaluacion\RecordAcademico($this->pdo))->de($alumno, $p['programa'], $c);
        // u1: 14 (recuperación), u2: 9, u3: 17.5 — todas de 3 créditos → (14 + 9 + 17.5) / 3
        self::assertSame(13.5, $record['promedio']);
        self::assertSame([6.0, 2], [$record['creditos_aprobados'], $record['unidades_aprobadas']]);
        self::assertSame(['UD-T2'], array_column($record['cargos'], 'codigo'), 'la desaprobada queda como cargo');
        self::assertFalse($record['cargos'][0]['recuperable']);

        $otroPeriodo = (int) $this->pdo->query('SELECT MAX(id_periodo) FROM periodos')->fetchColumn();
        $situacion = array_column($matricula->situacion($alumno, $p['programa'], $otroPeriodo), 'situacion', 'codigo');
        self::assertSame('CARGO', $situacion['UD-T2'], 'en el periodo siguiente aparece como cargo pendiente');
        self::assertSame(1, $matricula->matricular($alumno, $p['u2'], $otroPeriodo), 'y se vuelve a llevar solo esa unidad');
        self::assertSame([], (new \App\Institucion\Evaluacion\RecordAcademico($this->pdo))->de($alumno, $p['programa'], $c)['cargos'], 'mientras la lleva, no es cargo');
    }

    public function testDetalleParaCertificados(): void
    {
        $p = $this->plan();
        $c = new \App\Institucion\Configuracion(['institucion.tipo' => 'INSTITUTO']);
        $matricula = new \App\Institucion\MatriculaPorUnidad($this->pdo);
        $record = new \App\Institucion\Evaluacion\RecordAcademico($this->pdo);
        [$alumno, $periodo] = [$this->alumno(), $this->periodo()];
        $calificar = function (int $unidad, string $nota) use ($matricula, $alumno, $periodo, $c): void {
            $matricula->matricular($alumno, $unidad, $periodo);
            $id = (int) $this->pdo->query("SELECT MAX(id_matricula_unidad) FROM matricula_unidades WHERE id_alumno = $alumno AND id_unidad = $unidad")->fetchColumn();
            $matricula->registrarNota('calificar', $id, $nota, $c);
        };

        $vacio = $record->detalle($alumno, $p['programa']);
        self::assertCount(3, $vacio['unidades'], 'todas las unidades del programa, aunque no las haya llevado');
        self::assertNull($vacio['unidades'][0]['estado']);
        self::assertSame([['id_modulo' => $p['modulo'], 'nombre' => 'Gestión de soporte técnico', 'unidades' => 3, 'aprobadas' => 0, 'creditos' => 0.0, 'completo' => false]], $vacio['modulos']);
        self::assertFalse($vacio['completo']);

        $calificar($p['u1'], '15');
        $calificar($p['u2'], '16');
        $parcial = $record->detalle($alumno, $p['programa']);
        self::assertSame([2, 6.0, false], [$parcial['modulos'][0]['aprobadas'], $parcial['modulos'][0]['creditos'], $parcial['modulos'][0]['completo']]);

        // Una unidad desactivada sin haberla aprobado ya no se exige para el certificado.
        $this->sp('CALL SP_GUARDAR_UNIDAD(?, ?, ?, ?, ?, ?, ?, ?, ?)', [$p['u3'], $p['modulo'], 'UD-T3', 'Unidad UD-T3', 3, '3.0', 32, 32, 'INACTIVO']);
        $completo = $record->detalle($alumno, $p['programa']);
        self::assertTrue($completo['modulos'][0]['completo']);
        self::assertTrue($completo['completo'], 'con todos sus módulos completos, el programa también');
    }
}
