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
    // Quién cobra: el usuario de la sesión (aquí, el auxiliar 22). Antes quedaba siempre el 9.
    private const COBRA = 22;

    public function testRegistrarPagoDePensionCreaPagoEIngresoDeQuienCobra(): void
    {
        $antesIngresos = (int) $this->valor('SELECT COUNT(*) FROM ingresos');

        self::assertSame('1', (string) $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [31, 'PENSION', 36, 100, self::COBRA]));

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
        self::assertSame(self::COBRA, (int) $ingreso['id_user'], 'el ingreso queda a nombre de quien cobra');
    }

    public function testUnaPensionYaPagadaNoSeCobraDosVeces(): void
    {
        $antes = (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones');
        self::assertSame('2', (string) $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [32, 'PENSION', 36, 100, self::COBRA]));
        self::assertSame($antes, (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones'));
    }

    public function testUnConceptoInvalidoSeRechazaYNoSeGuarda(): void
    {
        // Antes: el ENUM en modo permisivo guardaba '' sin error.
        try {
            $this->llamar('SP_REGISTRAR_DETALLE_PENSION_PAGO', [31, 'CUALQUIER COSA', 36, 100, self::COBRA]);
            self::fail('Se esperaba un error');
        } catch (PDOException $e) {
            self::assertStringContainsString('Concepto de pago no válido', $e->getMessage());
        }
        self::assertSame(0, (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones WHERE id_matri = 31 AND id_pension = 36'));
    }

    // ------------------------------------------------------------------ Matrícula

    /** @return list<mixed> */
    private function matricular(int $alumno, int $anio, int $aula, string $usuario = ''): array
    {
        return [$alumno, $anio, $aula, 50, 30, 150, 'COLEGIO X', 'LIMA', 'LIMA', $usuario ?: 'nuevo' . $alumno,
            password_hash('x', PASSWORD_DEFAULT), "nuevo$alumno@example.com", self::COBRA];
    }

    /** @return array<string, array<string, mixed>> pagos de la matrícula, con su ingreso, por concepto */
    private function pagosEIngresosDe(int $alumno, int $anio): array
    {
        $matricula = (int) $this->valor('SELECT id_matricula FROM matricula WHERE id_alumno = ? AND `id_año` = ?', $alumno, $anio);
        $q = $this->pdo->query("SELECT p.concepto, p.id_pago_pension pago, p.sub_total monto,
                i.id_pago_pension ingreso_pago, i.id_user usuario, i.observacion
            FROM pago_pensiones p LEFT JOIN ingresos i ON i.id_pago_pension = p.id_pago_pension
            WHERE p.id_matri = $matricula ORDER BY p.id_pago_pension");
        $salida = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $salida[$f['concepto']] = $f;
        }
        return $salida;
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

    public function testCadaIngresoDeLaMatriculaApuntaASuPropioPagoYAQuienCobra(): void
    {
        $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(18, 5, 5));
        $filas = $this->pagosEIngresosDe(18, 5);
        self::assertSame(['ADMISION', 'ALUMNO NUEVO', 'MATRICULA'], array_keys($filas));
        foreach ($filas as $concepto => $f) {
            self::assertSame((int) $f['pago'], (int) $f['ingreso_pago'], "el ingreso de $concepto apunta a su pago");
            self::assertSame($concepto, $f['observacion']);
            self::assertSame(self::COBRA, (int) $f['usuario'], "el ingreso de $concepto es de quien cobra");
        }
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

    public function testAlumnoAntiguoTambienEnlazaCadaIngresoASuPago(): void
    {
        // Antes: @ULID no se recalculaba en esta rama y los ingresos quedaban sin pago (NULL).
        $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(6, 2, 5));
        $filas = $this->pagosEIngresosDe(6, 2);
        self::assertSame(['ADMISION', 'ALUMNO NUEVO', 'MATRICULA'], array_keys($filas));
        foreach ($filas as $concepto => $f) {
            self::assertSame((int) $f['pago'], (int) $f['ingreso_pago'], "el ingreso de $concepto apunta a su pago");
        }
    }

    // ------------------------------------------------------------------ Cuentas de usuario

    /** @return list<array<string, mixed>> */
    private function verificarUsuario(string $usuario): array
    {
        $q = $this->pdo->prepare('CALL SP_VERIFICAR_USUARIO(?)');
        $q->execute([$usuario]);
        $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        while ($q->nextRowset()) {
            // consume los conjuntos de resultados restantes
        }
        $q->closeCursor();
        return $filas;
    }

    public function testElLoginNoDistingueMayusculasEnElUsuario(): void
    {
        // Antes: comparaba con BINARY y solo entraba quien escribía las mayúsculas exactas.
        foreach (['usuario10', 'USUARIO10', 'Usuario10'] as $escrito) {
            $filas = $this->verificarUsuario($escrito);
            self::assertCount(1, $filas, "«{$escrito}» encuentra la cuenta");
            self::assertSame(10, (int) $filas[0]['usu_id']);
        }
    }

    public function testUnUsuarioLargoSeGuardaCompleto(): void
    {
        // Antes: USU VARCHAR(8) truncaba «usuariolargo19» a «usuariol» y la persona no podía entrar.
        $this->llamar('SP_REGISTRAR_MATRICULA', $this->matricular(19, 5, 5, 'usuariolargo19'));
        self::assertSame('usuariolargo19', $this->valor('SELECT u.usu_usuario FROM usuario u
            JOIN matricula m ON m.usu_id = u.usu_id WHERE m.id_alumno = 19'));
        self::assertCount(1, $this->verificarUsuario('USUARIOLARGO19'));
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

    public function testElConteoEsElTotalDeNotasInsertadas(): void
    {
        // Antes: ROW_COUNT() del último INSERT (aquí habría devuelto 1).
        $insertadas = $this->registrarNotas([
            ['id_matri' => 34, 'perio' => 12, 'cri' => 1, 'nota' => '15', 'conclu' => ''],
            ['id_matri' => 34, 'perio' => 12, 'cri' => 8, 'nota' => '16', 'conclu' => ''],
        ]);
        self::assertSame(2, (int) $this->valor('SELECT COUNT(*) FROM notas WHERE id_matricula = 34 AND id_bimestre = 12'));
        self::assertSame(2, (int) $insertadas);
    }

    public function testEnUnLoteMixtoSoloCuentanLasNuevas(): void
    {
        // 34/44/42 ya existe; la otra es nueva. Antes devolvía 1 o 0 según cuál fuera la última.
        $insertadas = $this->registrarNotas([
            ['id_matri' => 34, 'perio' => 12, 'cri' => 1, 'nota' => '15', 'conclu' => ''],
            ['id_matri' => 34, 'perio' => 44, 'cri' => 42, 'nota' => '20', 'conclu' => ''],
        ]);
        self::assertSame(1, (int) $insertadas);
    }

    public function testMatriculaInexistenteSeRechazaConSuMensaje(): void
    {
        // Antes: la variable sombreaba la columna y la validación nunca se lanzaba (fallaba la FK).
        try {
            $this->registrarNotas([['id_matri' => 999999, 'perio' => 12, 'cri' => 1, 'nota' => '10', 'conclu' => '']]);
            self::fail('Se esperaba un error');
        } catch (PDOException $e) {
            self::assertStringContainsString('no existe en la tabla matricula', $e->getMessage());
        }
    }

    // ------------------------------------------------------------------ Orden de los listados

    public function testLasMatriculasConElMismoCreatedAtSalenEnOrdenEstable(): void
    {
        // Antes: ORDER BY created_at (fecha sin hora) dejaba el orden de los empates al azar.
        $ids = function (): array {
            $q = $this->pdo->query('CALL SP_LISTAR_MATRICULADOS()');
            $filas = array_column($q->fetchAll(PDO::FETCH_ASSOC), 'id_matricula');
            while ($q->nextRowset()) {
                // consume
            }
            $q->closeCursor();
            return array_map('intval', $filas);
        };
        $primera = $ids();
        self::assertSame($primera, $ids());
        $fechas = $this->pdo->query('SELECT id_matricula, created_at FROM matricula')->fetchAll(PDO::FETCH_KEY_PAIR);
        for ($i = 1; $i < count($primera); $i++) {
            [$a, $b] = [$primera[$i - 1], $primera[$i]];
            if ($fechas[$a] === $fechas[$b]) {
                self::assertGreaterThan($b, $a, 'empate en created_at: va primero el id mayor');
            }
        }
    }
}
