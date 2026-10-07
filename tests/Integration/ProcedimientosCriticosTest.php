<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PDOException;

/**
 * Caracterización de los procedimientos de escritura críticos (Fase 2.2): pagos, matrícula y notas.
 *
 * Congela el comportamiento ACTUAL, defectos incluidos (marcados con «DEFECTO»): al corregirlos,
 * la prueba correspondiente debe cambiar a propósito, en el mismo commit que la corrección.
 *
 * Requiere database/seeders/datos_prueba.sql cargado. Fecha congelada en 2025-12-26 12:00:00.
 */
final class ProcedimientosCriticosTest extends BaseDatosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM usuario WHERE usu_id = 55')->fetchColumn() === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        // Como en la app (una conexión por petición): las variables de sesión empiezan vacías.
        $this->pdo->exec('SET @ULID = NULL, @VER = NULL');
    }

    /** @param list<mixed> $params */
    private function llamar(string $sp, array $params): mixed
    {
        $q = $this->pdo->prepare("CALL $sp(" . implode(',', array_fill(0, count($params), '?')) . ')');
        $q->execute($params);
        $valor = $q->fetchColumn();
        while ($q->nextRowset()) {
            // consume los conjuntos de resultados restantes
        }
        $q->closeCursor();
        return $valor;
    }

    private function valor(string $sql, mixed ...$params): mixed
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($params);
        return $q->fetchColumn();
    }

    // ------------------------------------------------------------------ Pagos de pensión

    public function testRegistrarPagoDePensionCreaPagoEIngreso(): void
    {
        $antesIngresos = (int) $this->valor('SELECT COUNT(*) FROM ingresos');

        self::assertSame('1', (string) $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [31, 'PENSION', 36, 100]));

        $pago = $this->pdo->query('SELECT * FROM pago_pensiones WHERE id_matri = 31 AND id_pension = 36')->fetch(PDO::FETCH_ASSOC);
        self::assertSame('PENSION', $pago['concepto']);
        self::assertSame('100.00', $pago['sub_total']);
        self::assertSame('2025-12-26', substr((string) $pago['fecha_pago'], 0, 10));

        $ingreso = $this->pdo->query('SELECT * FROM ingresos WHERE id_pago_pension = ' . (int) $pago['id_pago_pension'])
            ->fetch(PDO::FETCH_ASSOC);
        self::assertSame($antesIngresos + 1, (int) $this->valor('SELECT COUNT(*) FROM ingresos'));
        self::assertSame('100.00', $ingreso['monto']);
        self::assertSame('VALIDO', $ingreso['estado']);
        self::assertSame(1, (int) $ingreso['id_indicador']);
        // DEFECTO: el ingreso queda siempre a nombre del usuario 9, sin importar quién cobra (H-15).
        self::assertSame(9, (int) $ingreso['id_user']);
    }

    public function testUnaPensionYaPagadaNoSeCobraDosVeces(): void
    {
        $antes = (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones');
        self::assertSame('2', (string) $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [32, 'PENSION', 36, 100]));
        self::assertSame($antes, (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones'));
    }

    public function testDefectoUnConceptoInvalidoSeGuardaVacioSinError(): void
    {
        // DEFECTO: concepto es ENUM y el SP corre en modo SQL permisivo: un valor fuera de la lista
        // se guarda como '' sin error. (La interfaz solo envía valores válidos desde un select.)
        self::assertSame('1', (string) $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [31, 'CUALQUIER COSA', 36, 100]));
        self::assertSame('', $this->valor('SELECT concepto FROM pago_pensiones WHERE id_matri = 31 AND id_pension = 36'));
    }

    // ------------------------------------------------------------------ Matrícula

    /** @return list<mixed> */
    private function matricular(int $alumno, int $anio, int $aula): array
    {
        return [$alumno, $anio, $aula, 50, 30, 150, 'COLEGIO X', 'LIMA', 'LIMA', 'nuevo' . $alumno,
            password_hash('x', PASSWORD_DEFAULT), "nuevo$alumno@example.com"];
    }

    public function testMatricularAlumnoNuevoCreaUsuarioMatriculaYTresPagos(): void
    {
        $usuarios = (int) $this->valor('SELECT COUNT(*) FROM usuario');

        self::assertSame('1', (string) $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(18, 5, 5)));

        self::assertSame($usuarios + 1, (int) $this->valor('SELECT COUNT(*) FROM usuario'));
        $usuario = $this->pdo->query("SELECT * FROM usuario WHERE usu_usuario = 'nuevo18'")->fetch(PDO::FETCH_ASSOC);
        self::assertSame(1, (int) $usuario['rol_id'], 'el usuario nuevo es ESTUDIANTE');
        self::assertSame('ACTIVO', $usuario['usu_estatus']);

        $alumno = $this->pdo->query('SELECT alum_estatus, tipo_alum FROM alumnos WHERE Id_alumno = 18')->fetch(PDO::FETCH_ASSOC);
        self::assertSame(['alum_estatus' => 'SI', 'tipo_alum' => 'ANTIGUO'], $alumno);

        $matricula = $this->pdo->query('SELECT * FROM matricula WHERE id_alumno = 18')->fetch(PDO::FETCH_ASSOC);
        self::assertSame((int) $usuario['usu_id'], (int) $matricula['usu_id']);
        self::assertSame(5, (int) $matricula['id_aula']);

        $pagos = $this->pdo->query('SELECT concepto, sub_total FROM pago_pensiones WHERE id_matri = '
            . (int) $matricula['id_matricula'] . ' ORDER BY id_pago_pension')->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame(['ADMISION' => '50.00', 'ALUMNO NUEVO' => '30.00', 'MATRICULA' => '150.00'], $pagos);
    }

    public function testDefectoLosTresIngresosDeMatriculaApuntanAlUltimoPago(): void
    {
        $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(18, 5, 5));
        $ultimoPago = (int) $this->valor('SELECT MAX(id_pago_pension) FROM pago_pensiones');

        $ingresos = $this->pdo->query("SELECT observacion, id_pago_pension FROM ingresos
            WHERE observacion IN ('ADMISION','ALUMNO NUEVO','MATRICULA') AND id_pago_pension = $ultimoPago")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        // DEFECTO: ADMISION y ALUMNO NUEVO deberían apuntar a su propio pago, no al de MATRICULA.
        self::assertSame(['ADMISION', 'ALUMNO NUEVO', 'MATRICULA'], array_keys($ingresos));
    }

    public function testNoSeMatriculaDosVecesEnElMismoAnio(): void
    {
        $antes = (int) $this->valor('SELECT COUNT(*) FROM matricula');
        self::assertSame('2', (string) $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(9, 5, 18)));
        self::assertSame($antes, (int) $this->valor('SELECT COUNT(*) FROM matricula'));
    }

    public function testMatricularAlumnoAntiguoReutilizaSuUsuario(): void
    {
        $usuarios = (int) $this->valor('SELECT COUNT(*) FROM usuario');
        $usuarioPrevio = (int) $this->valor('SELECT usu_id FROM matricula WHERE id_alumno = 6 LIMIT 1');

        self::assertSame('1', (string) $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(6, 2, 5)));

        self::assertSame($usuarios, (int) $this->valor('SELECT COUNT(*) FROM usuario'), 'no crea usuario nuevo');
        self::assertSame($usuarioPrevio, (int) $this->valor('SELECT usu_id FROM matricula WHERE id_alumno = 6 AND `id_año` = 2'));
    }

    public function testDefectoAlumnoAntiguoDejaLosIngresosSinPagoAsociado(): void
    {
        $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(6, 2, 5));
        // DEFECTO: en esta rama no se recalcula @ULID; con una conexión nueva vale NULL y los tres
        // ingresos quedan sin pago asociado (con otro valor previo en la sesión, apuntarían a otro pago).
        $sinPago = (int) $this->valor("SELECT COUNT(*) FROM ingresos WHERE id_pago_pension IS NULL
            AND observacion IN ('ADMISION','ALUMNO NUEVO','MATRICULA') AND created_at = '2025-12-26'");
        self::assertSame(3, $sinPago);
    }

    // ------------------------------------------------------------------ Notas

    private function registrarNotas(array $registros): mixed
    {
        return $this->llamar('SP_REGISTRAR_NOTAS', [json_encode($registros)]);
    }

    public function testRegistrarNotasNuevas(): void
    {
        $this->registrarNotas([['id_matri' => 34, 'perio' => 12, 'cri' => 1, 'nota' => '17', 'conclu' => 'Bien']]);
        self::assertSame('17', trim((string) $this->valor(
            'SELECT nota FROM notas WHERE id_matricula = 34 AND id_bimestre = 12 AND id_criterio = 1'
        )));
    }

    public function testUnaNotaYaRegistradaNoSeSobrescribe(): void
    {
        // Existe 34/44/42 = 12. Volver a registrarla con otro valor no la cambia ni la duplica.
        $this->registrarNotas([['id_matri' => 34, 'perio' => 44, 'cri' => 42, 'nota' => '20', 'conclu' => '']]);
        $q = $this->pdo->query('SELECT nota FROM notas WHERE id_matricula = 34 AND id_bimestre = 44 AND id_criterio = 42');
        self::assertSame(['12'], array_map('trim', $q->fetchAll(PDO::FETCH_COLUMN)));
    }

    public function testDefectoElConteoDevueltoEsSoloDelUltimoRegistro(): void
    {
        $insertadas = $this->registrarNotas([
            ['id_matri' => 34, 'perio' => 12, 'cri' => 1, 'nota' => '15', 'conclu' => ''],
            ['id_matri' => 34, 'perio' => 12, 'cri' => 8, 'nota' => '16', 'conclu' => ''],
        ]);
        self::assertSame(2, (int) $this->valor('SELECT COUNT(*) FROM notas WHERE id_matricula = 34 AND id_bimestre = 12'));
        // DEFECTO: ROW_COUNT() refleja solo el último INSERT, no el total insertado.
        self::assertSame(1, (int) $insertadas);
    }

    public function testDefectoMatriculaInexistenteFallaPorLaClaveForaneaNoPorLaValidacion(): void
    {
        // DEFECTO: «WHERE id_matricula = id_matricula» compara la variable consigo misma (sombrea la
        // columna), así que el mensaje «id_matricula no existe» nunca se lanza; falla la FK.
        try {
            $this->registrarNotas([['id_matri' => 999999, 'perio' => 12, 'cri' => 1, 'nota' => '10', 'conclu' => '']]);
            self::fail('Se esperaba un error');
        } catch (PDOException $e) {
            self::assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
            self::assertStringNotContainsString('no existe en la tabla matricula', $e->getMessage());
        }
    }
}
