<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Caja\Movimiento;
use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Repositories\PdoCajaRepositorio;
use App\Repositories\PdoMatriculaRepositorio;
use App\Services\AnularMovimiento;
use Tests\Unit\Matricula\DatosMatriculaTest;

/**
 * Migraciones 20261015000000 (montos DECIMAL(10,2)), 20261016000000 (editar los montos de una
 * matrícula actualiza sus pagos) y 20261017000000 (anular conserva al responsable).
 */
final class MontosYAnulacionTest extends BaseDatosTestCase
{
    private const COBRA = 22;
    private const ANULA = 9;
    private PdoMatriculaRepositorio $matriculas;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor("SELECT COUNT(*) FROM alumnos WHERE Id_alumno = 20 AND tipo_alum = 'NUEVO'") === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        $this->matriculas = new PdoMatriculaRepositorio($this->pdo);
    }

    private function valor(string $sql, int|string ...$params): mixed
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($params);
        $v = $q->fetchColumn();
        $q->closeCursor();
        return $v;
    }

    private function matricular(array $montos): int
    {
        $this->matriculas->registrar(
            20,
            DatosMatricula::desdeFormulario(DatosMatriculaTest::formulario($montos)),
            CuentaNueva::desdeFormulario(DatosMatriculaTest::formulario(['usu' => 'montos.it'])),
            self::COBRA,
        );
        return (int) $this->valor('SELECT id_matricula FROM matricula WHERE id_alumno = 20 AND `id_año` = 5');
    }

    /** @return array<string, array{monto: string, ingreso: string, estado: string, id_ingreso: int}> por concepto */
    private function pagos(int $matricula): array
    {
        $q = $this->pdo->prepare('SELECT p.concepto, p.sub_total, i.monto, i.estado, i.id_ingreso FROM pago_pensiones p
            JOIN ingresos i ON i.id_pago_pension = p.id_pago_pension WHERE p.id_matri = ?');
        $q->execute([$matricula]);
        $salida = [];
        foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $f) {
            $salida[$f['concepto']] = ['monto' => $f['sub_total'], 'ingreso' => $f['monto'], 'estado' => $f['estado'], 'id_ingreso' => (int) $f['id_ingreso']];
        }
        return $salida;
    }

    public function testUnMontoMayorA999SeGuardaCompleto(): void
    {
        $m = $this->matricular(['admi' => '1500', 'nuevo' => '250.50', 'matri' => '12345.67']);
        self::assertSame('1500.00', $this->valor('SELECT pago_admi FROM matricula WHERE id_matricula = ?', $m));
        $pagos = $this->pagos($m);
        self::assertSame('1500.00', $pagos['ADMISION']['monto']);
        self::assertSame('12345.67', $pagos['MATRICULA']['ingreso'], 'antes se recortaba a 999.99');
    }

    public function testEditarLosMontosActualizaLosPagosYSusIngresosValidos(): void
    {
        $m = $this->matricular(['admi' => '100', 'nuevo' => '50', 'matri' => '200']);
        $anulado = $this->pagos($m)['ALUMNO NUEVO']['id_ingreso'];
        (new AnularMovimiento(new PdoCajaRepositorio($this->pdo)))->ejecutar(Movimiento::Ingreso, $anulado, 'error de cobro', self::ANULA);

        self::assertSame(1, $this->matriculas->modificar($m, DatosMatricula::desdeFormulario(DatosMatriculaTest::formulario(['admi' => '120', 'nuevo' => '60', 'matri' => '1800']))));
        $pagos = $this->pagos($m);
        self::assertSame(['120.00', '120.00'], [$pagos['ADMISION']['monto'], $pagos['ADMISION']['ingreso']]);
        self::assertSame(['1800.00', '1800.00'], [$pagos['MATRICULA']['monto'], $pagos['MATRICULA']['ingreso']]);
        self::assertSame('60.00', $pagos['ALUMNO NUEVO']['monto'], 'el pago sigue al monto');
        self::assertSame('50.00', $pagos['ALUMNO NUEVO']['ingreso'], 'el ingreso anulado conserva lo que se anuló');
    }

    public function testAnularConservaQuienCobroYRegistraQuienAnulo(): void
    {
        $m = $this->matricular(['admi' => '100', 'nuevo' => '0', 'matri' => '200']);
        $ingreso = $this->pagos($m)['ADMISION']['id_ingreso'];
        $anular = new AnularMovimiento(new PdoCajaRepositorio($this->pdo));

        self::assertTrue($anular->ejecutar(Movimiento::Ingreso, $ingreso, 'cobro duplicado', self::ANULA));
        $fila = $this->pdo->query("SELECT id_user, id_usuario_anulacion, estado, motivo_anulacion FROM ingresos WHERE id_ingreso = $ingreso")->fetch(\PDO::FETCH_ASSOC);
        self::assertSame(
            [self::COBRA, self::ANULA, 'ANULADO', 'COBRO DUPLICADO'],
            [(int) $fila['id_user'], (int) $fila['id_usuario_anulacion'], $fila['estado'], $fila['motivo_anulacion']]
        );
        self::assertFalse($anular->ejecutar(Movimiento::Ingreso, $ingreso, 'otra vez', 1), 'ya anulado: no se reescribe');
        self::assertSame(self::ANULA, (int) $this->valor('SELECT id_usuario_anulacion FROM ingresos WHERE id_ingreso = ?', $ingreso));
        self::assertFalse($anular->ejecutar(Movimiento::Ingreso, 99999999, 'x', self::ANULA), 'inexistente');
    }

    public function testAnularUnEgresoTambienConservaQuienPago(): void
    {
        $egreso = (int) $this->valor("SELECT id_egresos FROM egresos WHERE estado = 'VALIDO' ORDER BY id_egresos LIMIT 1");
        $pago = (int) $this->valor('SELECT id_user FROM egresos WHERE id_egresos = ?', $egreso);
        self::assertTrue((new AnularMovimiento(new PdoCajaRepositorio($this->pdo)))->ejecutar(Movimiento::Egreso, $egreso, 'mal registrado', 10));
        self::assertSame([$pago, 10], [
            (int) $this->valor('SELECT id_user FROM egresos WHERE id_egresos = ?', $egreso),
            (int) $this->valor('SELECT id_usuario_anulacion FROM egresos WHERE id_egresos = ?', $egreso),
        ]);
    }
}
