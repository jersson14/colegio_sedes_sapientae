<?php

declare(strict_types=1);

namespace Tests\Unit\Facturacion;

use App\Facturacion\ConfiguracionNubefact;
use App\Facturacion\DocumentoNubefact;
use App\Facturacion\EmisionComprobantes;
use PHPUnit\Framework\TestCase;

final class DocumentoNubefactTest extends TestCase
{
    private static function cfg(string $afectacion = 'EXONERADO', bool $enviar = false): ConfiguracionNubefact
    {
        return new ConfiguracionNubefact('https://api.nubefact.com/api/v1/abc', 't', 'B001', 'F001', $afectacion, $enviar);
    }

    /** @return array{tipo: int, serie: string, numero: int, cliente_tipo_doc: string, cliente_doc: string, cliente_nombre: string, cliente_direccion: string, cliente_email: string, descripcion: string, total: string} */
    private static function comprobante(string $total = '350.00'): array
    {
        return ['tipo' => 2, 'serie' => 'B001', 'numero' => 7, 'cliente_tipo_doc' => '1', 'cliente_doc' => '70000001', 'cliente_nombre' => 'ANA PEREZ',
            'cliente_direccion' => '', 'cliente_email' => 'ana@example.com', 'descripcion' => 'PENSION MARZO - JUAN PEREZ', 'total' => $total];
    }

    public function testExoneradoSinIgv(): void
    {
        $d = DocumentoNubefact::comprobante(self::comprobante(), self::cfg(), 1, new \DateTimeImmutable('2026-03-05'));
        self::assertSame(['generar_comprobante', 2, 'B001', 7, '05-03-2026', 1], [$d['operacion'], $d['tipo_de_comprobante'], $d['serie'], $d['numero'], $d['fecha_de_emision'], $d['moneda']]);
        self::assertSame([350.0, '', 0.0, 350.0], [$d['total_exonerada'], $d['total_gravada'], $d['total_igv'], $d['total']]);
        self::assertSame([8, 350.0, 350.0, 0.0], [$d['items'][0]['tipo_de_igv'], $d['items'][0]['valor_unitario'], $d['items'][0]['subtotal'], $d['items'][0]['igv']]);
        self::assertFalse($d['enviar_automaticamente_al_cliente']);
    }

    public function testGravadoSeparaElIgv(): void
    {
        $d = DocumentoNubefact::comprobante(self::comprobante('118.00'), self::cfg('GRAVADO', true), 1, new \DateTimeImmutable('2026-03-05'));
        self::assertSame([100.0, 18.0, 118.0, ''], [$d['total_gravada'], $d['total_igv'], $d['total'], $d['total_exonerada']]);
        self::assertSame([1, 100.0, 118.0], [$d['items'][0]['tipo_de_igv'], $d['items'][0]['valor_unitario'], $d['items'][0]['precio_unitario']]);
        self::assertTrue($d['enviar_automaticamente_al_cliente'], 'con correo y la opción activa');
    }

    public function testCantidadSoloSiElTotalSeReparteExacto(): void
    {
        $tres = DocumentoNubefact::comprobante(self::comprobante('30.00'), self::cfg(), 3, new \DateTimeImmutable());
        self::assertSame([3, 10.0], [$tres['items'][0]['cantidad'], $tres['items'][0]['precio_unitario']]);
        $inexacto = DocumentoNubefact::comprobante(self::comprobante('10.00'), self::cfg(), 3, new \DateTimeImmutable());
        self::assertSame([1, 10.0], [$inexacto['items'][0]['cantidad'], $inexacto['items'][0]['precio_unitario']]);
    }

    public function testResultadoDeNubefact(): void
    {
        self::assertSame('ACEPTADO', DocumentoNubefact::resultado(['aceptada_por_sunat' => true, 'enlace_del_pdf' => 'https://x/a.pdf'])['estado']);
        $emitido = DocumentoNubefact::resultado(['aceptada_por_sunat' => false, 'enlace_del_pdf' => 'https://x/a.pdf']);
        self::assertSame(['EMITIDO', 'https://x/a.pdf'], [$emitido['estado'], $emitido['pdf']]);
        $error = DocumentoNubefact::resultado(['errors' => 'Documento inválido', 'codigo' => 21]);
        self::assertSame(['ERROR', 'Documento inválido (código 21)'], [$error['estado'], $error['error']]);
    }

    public function testAnulacionYConsulta(): void
    {
        self::assertSame(['operacion' => 'consultar_comprobante', 'tipo_de_comprobante' => 1, 'serie' => 'F001', 'numero' => 3], DocumentoNubefact::consulta(1, 'F001', 3));
        self::assertSame('generar_anulacion', DocumentoNubefact::anulacion(2, 'B001', 3, 'ERROR')['operacion']);
    }

    public function testRutaSoloDeNubefact(): void
    {
        self::assertSame('https://api.nubefact.com/api/v1/03989d1a-6c8c', ConfiguracionNubefact::validarRuta(' https://api.nubefact.com/api/v1/03989d1a-6c8c '));
        $malas = ['http://api.nubefact.com/api/v1/x', 'https://api.nubefact.com.evil.com/api/v1/x', 'https://169.254.169.254/api/v1/x',
            'https://api.nubefact.com:8443/api/v1/x', 'https://api.nubefact.com/otra/x', 'file:///etc/passwd'];
        foreach ($malas as $mala) {
            try {
                ConfiguracionNubefact::validarRuta($mala);
                self::fail("Aceptó $mala");
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
        self::assertSame('http://127.0.0.1:8097/api', ConfiguracionNubefact::validarRuta('http://127.0.0.1:8097/api', ['127.0.0.1:8097']));
    }

    public function testSeries(): void
    {
        self::assertSame('B002', ConfiguracionNubefact::validarSerie(' b002 ', 'B'));
        $this->expectException(\InvalidArgumentException::class);
        ConfiguracionNubefact::validarSerie('F001', 'B');
    }

    public function testClienteSegunElComprobante(): void
    {
        $boleta = EmisionComprobantes::validarCliente(2, ['tipo_doc' => '-', 'doc' => '', 'nombre' => ''], 50);
        self::assertSame(['-', '-', 'CLIENTES VARIOS'], [$boleta['tipo_doc'], $boleta['doc'], $boleta['nombre']]);
        self::assertSame('ANA PEREZ', EmisionComprobantes::validarCliente(2, ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'ana perez'], 900)['nombre']);
        $factura = EmisionComprobantes::validarCliente(1, ['tipo_doc' => '6', 'doc' => '20123456789', 'nombre' => 'Empresa SAC', 'direccion' => 'Av. 1'], 900);
        self::assertSame('EMPRESA SAC', $factura['nombre']);
        $casos = [
            [2, ['tipo_doc' => '-', 'doc' => '', 'nombre' => 'X'], 700.0, 'desde S/ 700 exige documento'],
            [1, ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'X', 'direccion' => 'Av'], 10.0, 'factura sin RUC'],
            [1, ['tipo_doc' => '6', 'doc' => '20123456789', 'nombre' => 'X', 'direccion' => ''], 10.0, 'factura sin dirección'],
            [2, ['tipo_doc' => '1', 'doc' => '123', 'nombre' => 'X'], 10.0, 'DNI corto'],
            [2, ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'X', 'email' => 'no-es-correo'], 10.0, 'correo inválido'],
        ];
        foreach ($casos as [$tipo, $cliente, $total, $caso]) {
            try {
                EmisionComprobantes::validarCliente($tipo, $cliente, $total);
                self::fail("Aceptó: $caso");
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
