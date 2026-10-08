<?php

declare(strict_types=1);

namespace Tests\Unit\Pension;

use App\Domain\Caja\MovimientoDiverso;
use App\Domain\Pension\DatosPension;
use App\Domain\Pension\Pago;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PensionYPagoTest extends TestCase
{
    public function testLeeLasFilasDelCobro(): void
    {
        $pagos = Pago::listaDesdeFormulario(['id_matri' => '31,31', 'concepto' => 'PENSION,matricula', 'id_pension' => '36,', 'monto' => '100,1500.5']);
        self::assertCount(2, $pagos);
        self::assertSame([31, 'PENSION', 36, '100.00'], [$pagos[0]->matricula, $pagos[0]->concepto, $pagos[0]->pension, $pagos[0]->monto->valor]);
        self::assertSame(['MATRICULA', null, '1500.50'], [$pagos[1]->concepto, $pagos[1]->pension, $pagos[1]->monto->valor]);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function cobrosInvalidos(): iterable
    {
        $base = ['id_matri' => '31', 'concepto' => 'PENSION', 'id_pension' => '36', 'monto' => '100'];
        yield 'listas que no cuadran' => [['id_matri' => '31,32'] + $base];
        yield 'concepto desconocido' => [['concepto' => 'DONACION'] + $base];
        yield 'pensión sin id' => [['id_pension' => ''] + $base];
        yield 'monto con coma decimal' => [['monto' => '10,5'] + $base];
    }

    /** @param array<string, string> $post */
    #[DataProvider('cobrosInvalidos')]
    public function testRechazaCobrosMalFormados(array $post): void
    {
        $this->expectException(InvalidArgumentException::class);
        Pago::listaDesdeFormulario($post);
    }

    public function testDatosDeLaPension(): void
    {
        $p = DatosPension::desdeFormulario(['nivel' => '1', 'mes' => 'marzo', 'fecha' => '2027-03-31', 'precio' => '150', 'mora' => '']);
        self::assertSame(['MARZO', '2027-03-31', '150.00', '0.00'], [$p->mes, $p->vencimiento, $p->precio->valor, $p->mora->valor]);
        $this->expectException(InvalidArgumentException::class);
        DatosPension::desdeFormulario(['nivel' => '1', 'mes' => 'MARZOO', 'fecha' => '2027-03-31', 'precio' => '150']);
    }

    public function testMovimientoDiversoAceptaLosDosNombresDeObservacion(): void
    {
        // El registro envía «obse» y la edición «obser».
        self::assertSame('UNIFORMES', MovimientoDiverso::desdeFormulario(['indi' => '4', 'cantidad' => '1', 'monto' => '5', 'obser' => 'uniformes'])->observacion);
        $this->expectException(InvalidArgumentException::class);
        MovimientoDiverso::desdeFormulario(['indi' => '4', 'cantidad' => '0', 'monto' => '5', 'obse' => '']);
    }
}
