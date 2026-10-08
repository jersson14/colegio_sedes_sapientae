<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Caja\Movimiento;
use App\Domain\Caja\MovimientoDiverso;
use App\Domain\Monto;
use App\Domain\Pension\DatosPension;
use App\Domain\Pension\Pago;
use App\Repositories\PdoCajaRepositorio;
use App\Repositories\PdoPensionRepositorio;

/** Migración 20261019000000: pensiones, pagos e ingresos/egresos diversos. */
final class PagosYCajaTest extends BaseDatosTestCase
{
    private const COBRA = 22;
    private PdoPensionRepositorio $pensiones;
    private PdoCajaRepositorio $caja;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor('SELECT COUNT(*) FROM pensiones WHERE id_pensiones = 36') === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        $this->pensiones = new PdoPensionRepositorio($this->pdo);
        $this->caja = new PdoCajaRepositorio($this->pdo);
    }

    private function valor(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    /** @param array<string, string> $post */
    private static function diverso(array $post): MovimientoDiverso
    {
        return MovimientoDiverso::desdeFormulario($post + ['indi' => '4', 'cantidad' => '1', 'monto' => '50', 'obse' => 'prueba it']);
    }

    public function testLaPensionDelMismoMesEnOtroAnioSiSeRegistra(): void
    {
        $marzo = static fn (string $fecha): DatosPension => DatosPension::desdeFormulario(['nivel' => '1', 'mes' => 'MARZO', 'fecha' => $fecha, 'precio' => '150', 'mora' => '5']);
        self::assertSame(2, $this->pensiones->registrar($marzo('2026-03-15')), 'la de 2026 ya existe (pensión 36)');
        self::assertSame(1, $this->pensiones->registrar($marzo('2027-03-31')), 'antes respondía «ya existe» para siempre');
        self::assertSame(2, $this->pensiones->modificar(36, $marzo('2027-03-10')), 'no se puede mover sobre la de 2027');
    }

    public function testUnaPensionConPagosNoSeElimina(): void
    {
        self::assertFalse($this->pensiones->eliminar(36));
        self::assertSame(1, (int) $this->valor('SELECT COUNT(*) FROM pensiones WHERE id_pensiones = 36'));
    }

    public function testElCobroEsTodoONada(): void
    {
        $pagos = Pago::listaDesdeFormulario(['id_matri' => '32,38', 'concepto' => 'PENSION,PENSION', 'id_pension' => '56,56', 'monto' => '100,100']);
        $antes = (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones WHERE id_matri = 32 AND id_pension = 56');
        // La matrícula 38 ya pagó la pensión 56: el primero (32) no debe quedar cobrado.
        self::assertFalse($this->pensiones->cobrar($pagos, self::COBRA));
        self::assertSame($antes, (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones WHERE id_matri = 32 AND id_pension = 56'));
    }

    public function testEditarUnPagoMueveSuIngresoYAnularloLoConservaEnCaja(): void
    {
        // Pago sembrado de la matrícula 38 (pensión 56), con su ingreso válido.
        $pago = (int) $this->valor("SELECT id_pago_pension FROM pago_pensiones WHERE id_matri = 38 AND id_pension = 56 AND concepto = 'PENSION'");
        $ingreso = (int) $this->valor("SELECT id_ingreso FROM ingresos WHERE id_pago_pension = $pago AND estado = 'VALIDO'");
        self::assertTrue($this->pensiones->modificarPago($pago, Monto::desde('95', 'm'), 'DESCUENTO'));
        self::assertSame('95.00', $this->valor("SELECT monto FROM ingresos WHERE id_ingreso = $ingreso"));

        self::assertTrue($this->pensiones->anularPago($pago, 9));
        $fila = $this->pdo->query("SELECT estado, id_pago_pension, id_usuario_anulacion, motivo_anulacion FROM ingresos WHERE id_ingreso = $ingreso")->fetch(\PDO::FETCH_ASSOC);
        self::assertSame('ANULADO', $fila['estado']);
        self::assertNull($fila['id_pago_pension'], 'desligado del pago');
        self::assertSame(9, (int) $fila['id_usuario_anulacion']);
        self::assertStringStartsWith('PAGO ANULADO: PENSION', (string) $fila['motivo_anulacion']);
        self::assertSame(0, (int) $this->valor("SELECT COUNT(*) FROM pago_pensiones WHERE id_pago_pension = $pago"));
        self::assertTrue(
            $this->pensiones->cobrar(Pago::listaDesdeFormulario(['id_matri' => '38', 'concepto' => 'PENSION', 'id_pension' => '56', 'monto' => '100']), self::COBRA),
            'la pensión queda libre para volver a cobrarla'
        );
        self::assertFalse($this->pensiones->anularPago(99999999, 9));
    }

    public function testIngresosDiversosConservanQuienCobro(): void
    {
        self::assertFalse($this->caja->registrar(Movimiento::Ingreso, self::diverso(['indi' => '2']), self::COBRA), 'indicador de gastos');
        self::assertTrue($this->caja->registrar(Movimiento::Ingreso, self::diverso([]), self::COBRA));
        $id = (int) $this->valor("SELECT MAX(id_ingreso) FROM ingresos WHERE observacion = 'PRUEBA IT'");
        self::assertTrue($this->caja->modificar(Movimiento::Ingreso, $id, self::diverso(['monto' => '70'])));
        self::assertSame([self::COBRA, '70.00'], [(int) $this->valor("SELECT id_user FROM ingresos WHERE id_ingreso = $id"), $this->valor("SELECT monto FROM ingresos WHERE id_ingreso = $id")]);

        $dePension = (int) $this->valor("SELECT id_ingreso FROM ingresos WHERE id_pago_pension IS NOT NULL AND estado = 'VALIDO' LIMIT 1");
        self::assertFalse($this->caja->modificar(Movimiento::Ingreso, $dePension, self::diverso([])), 'se edita desde su pago');
        $this->caja->anular(Movimiento::Ingreso, $id, 'X', 9);
        self::assertFalse($this->caja->modificar(Movimiento::Ingreso, $id, self::diverso(['monto' => '80'])), 'anulado');
    }

    public function testEgresosYIndicadores(): void
    {
        self::assertFalse($this->caja->registrar(Movimiento::Egreso, self::diverso(['indi' => '4']), self::COBRA), 'indicador de ingresos');
        self::assertTrue($this->caja->registrar(Movimiento::Egreso, self::diverso(['indi' => '2']), self::COBRA));
        self::assertFalse($this->caja->eliminarIndicador(1), 'en uso');
        $this->pdo->exec("INSERT INTO indicadores (tipo_indicador, nombre, estado) VALUES ('GASTOS', 'SIN USO', 'ACTIVO')");
        self::assertTrue($this->caja->eliminarIndicador((int) $this->pdo->lastInsertId()));
    }
}
