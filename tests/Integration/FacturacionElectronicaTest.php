<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Facturacion\ClienteNubefact;
use App\Facturacion\ConfiguracionNubefact;
use App\Facturacion\EmisionComprobantes;
use App\Facturacion\FalloConexionNubefact;
use App\Facturacion\PdoConfiguracionFacturacion;
use App\Support\Cifrado;

/** Facturación electrónica (migración 20261102000000) con un Nubefact en memoria. */
final class FacturacionElectronicaTest extends BaseDatosTestCase
{
    /** @var list<array<string, mixed>> */
    private array $enviados = [];

    /** @var list<array<string, mixed>|null> respuestas en orden; null = sin conexión */
    private array $respuestas = [];

    private function emision(): EmisionComprobantes
    {
        $prueba = $this;
        $cliente = new class ($prueba) implements ClienteNubefact {
            public function __construct(private readonly FacturacionElectronicaTest $prueba)
            {
            }

            public function enviar(string $ruta, string $token, array $cuerpo): array
            {
                return $this->prueba->responder($cuerpo);
            }
        };
        return new EmisionComprobantes($this->pdo, $cliente, new ConfiguracionNubefact('https://api.nubefact.com/api/v1/x', 't', 'B001', 'F001', 'EXONERADO', false));
    }

    /**
     * @param array<string, mixed> $cuerpo
     * @return array<string, mixed>
     */
    public function responder(array $cuerpo): array
    {
        $this->enviados[] = $cuerpo;
        $respuesta = array_shift($this->respuestas) ?? ['aceptada_por_sunat' => false, 'enlace_del_pdf' => 'https://nubefact/x.pdf'];
        if ($respuesta === ['sin_conexion' => true]) {
            throw new FalloConexionNubefact('Nubefact no respondió.');
        }
        return $respuesta;
    }

    /** @return list<int> ingresos válidos con monto, sin comprobante */
    private function ingresos(int $cuantos): array
    {
        return array_slice(array_column(EmisionComprobantes::pendientes($this->pdo), 'id_ingreso'), 0, $cuantos);
    }

    /** @return array<string, mixed> */
    private function comprobante(int $id): array
    {
        return (array) $this->pdo->query("SELECT * FROM comprobantes_electronicos WHERE id = $id")->fetch(\PDO::FETCH_ASSOC);
    }

    public function testEmiteBoletasCorrelativasYNoDuplica(): void
    {
        $ingresos = $this->ingresos(2);
        self::assertCount(2, $ingresos, 'el dataset trae ingresos con monto');
        [$a, $b] = $ingresos;
        $this->pdo->exec('DELETE FROM series_comprobantes');
        $cliente = ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'Ana Perez'];
        $primero = $this->emision()->emitir($a, 2, $cliente, 9);
        $segundo = $this->emision()->emitir($b, 2, $cliente, 9);
        self::assertSame([1, 1], [$primero['codigo'], $segundo['codigo']], $primero['mensaje']);
        self::assertSame([1, 2], [(int) $primero['comprobante']['numero'], (int) $segundo['comprobante']['numero']]);
        self::assertSame(['EMITIDO', 'https://nubefact/x.pdf', 'ANA PEREZ'], [$primero['comprobante']['estado'], $primero['comprobante']['enlace_pdf'], $primero['comprobante']['cliente_nombre']]);
        self::assertSame('B001', $this->enviados[0]['serie']);
        self::assertSame(0, $this->emision()->emitir($a, 2, $cliente, 9)['codigo'], 'un ingreso con comprobante no se vuelve a emitir');
        self::assertNotContains($a, array_column(EmisionComprobantes::pendientes($this->pdo), 'id_ingreso'));
    }

    public function testRechazoSeCorrigeConElMismoNumero(): void
    {
        [$a] = $this->ingresos(1);
        $this->respuestas = [['errors' => 'Documento inválido', 'codigo' => 21], ['aceptada_por_sunat' => true]];
        $rechazado = $this->emision()->emitir($a, 2, ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'Ana'], 9);
        self::assertSame([2, 'ERROR'], [$rechazado['codigo'], $rechazado['comprobante']['estado']]);
        $id = (int) $rechazado['comprobante']['id'];
        $corregido = $this->emision()->reintentar($id, ['tipo_doc' => '1', 'doc' => '70000002', 'nombre' => 'Ana Perez']);
        self::assertSame([1, 'ACEPTADO', '70000002'], [$corregido['codigo'], $corregido['comprobante']['estado'], $corregido['comprobante']['cliente_doc']]);
        self::assertSame($this->enviados[0]['numero'], $this->enviados[1]['numero'], 'mismo número');
    }

    public function testSinRespuestaQuedaPendienteYSeConsulta(): void
    {
        [$a] = $this->ingresos(1);
        $this->respuestas = [['sin_conexion' => true], ['errors' => 'El documento no existe.', 'codigo' => 24], ['aceptada_por_sunat' => false]];
        $pendiente = $this->emision()->emitir($a, 2, ['tipo_doc' => '-', 'doc' => '', 'nombre' => ''], 9);
        self::assertSame([3, 'PENDIENTE'], [$pendiente['codigo'], $pendiente['comprobante']['estado']]);
        $consultado = $this->emision()->consultar((int) $pendiente['comprobante']['id']);
        self::assertSame('EMITIDO', $consultado['comprobante']['estado'], 'si Nubefact no lo tiene, se reenvía');
        self::assertSame(['generar_comprobante', 'consultar_comprobante', 'generar_comprobante'], array_column($this->enviados, 'operacion'));
    }

    public function testAnulacion(): void
    {
        [$a] = $this->ingresos(1);
        $emitido = $this->emision()->emitir($a, 2, ['tipo_doc' => '1', 'doc' => '70000001', 'nombre' => 'Ana'], 9);
        $id = (int) $emitido['comprobante']['id'];
        self::assertSame(0, $this->emision()->anular($id, 'x')['codigo'], 'exige el motivo');
        $this->respuestas = [['numero' => 1, 'aceptada_por_sunat' => false]];
        self::assertSame(1, $this->emision()->anular($id, 'Error en el monto')['codigo']);
        self::assertSame(['ANULADO', 'ERROR EN EL MONTO'], [$this->comprobante($id)['estado'], $this->comprobante($id)['motivo_anulacion']]);
        self::assertContains($a, array_column(EmisionComprobantes::pendientes($this->pdo), 'id_ingreso'), 'anulado, el ingreso puede facturarse de nuevo');
    }

    public function testConfiguracionConTokenCifradoYCorrelativo(): void
    {
        $cifrado = Cifrado::desdeClave(base64_encode(str_repeat('k', 32)));
        $repositorio = new PdoConfiguracionFacturacion($this->pdo);
        $this->pdo->exec("DELETE FROM configuracion WHERE clave LIKE 'facturacion.%'");
        $repositorio->guardar(['ruta' => 'https://api.nubefact.com/api/v1/abc', 'token' => 'secreto', 'serie_boleta' => 'b002',
            'serie_factura' => 'F002', 'afectacion' => 'exonerado', 'ultimo_boleta' => '350'], $cifrado);
        $guardado = (string) $this->pdo->query("SELECT valor FROM configuracion WHERE clave = 'facturacion.token'")->fetchColumn();
        self::assertStringNotContainsString('secreto', $guardado);
        $cuenta = $repositorio->cargar($cifrado);
        self::assertSame(['secreto', 'B002', 'EXONERADO'], [$cuenta?->token, $cuenta?->serieBoleta, $cuenta?->afectacion]);
        self::assertSame(350, $repositorio->resumen()['ultimo_boleta']);
        self::assertArrayNotHasKey('token', $repositorio->resumen());

        $repositorio->guardar(['ruta' => 'https://api.nubefact.com/api/v1/abc', 'token' => '', 'serie_boleta' => 'B002', 'serie_factura' => 'F002',
            'afectacion' => 'EXONERADO', 'ultimo_boleta' => '10'], $cifrado);
        self::assertSame('secreto', $repositorio->cargar($cifrado)?->token, 'token vacío: se conserva');
        self::assertSame(350, $repositorio->resumen()['ultimo_boleta'], 'la numeración nunca retrocede');
    }
}
