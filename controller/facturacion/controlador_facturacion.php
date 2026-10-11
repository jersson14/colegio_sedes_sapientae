<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../core/modulos.php';
exigir_modulo(App\Comercial\ModuloOpcional::FacturacionElectronica);
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Facturacion\EmisionComprobantes;
use App\Facturacion\HttpClienteNubefact;
use App\Facturacion\PdoConfiguracionFacturacion;
use App\Support\Cifrado;

/**
 * Facturación electrónica con la cuenta de Nubefact de la institución (módulo opcional).
 *   GET                                     → configuración (sin el token), ingresos sin comprobante y lo emitido
 *   POST accion=configurar                  (ruta, token, serie_boleta, serie_factura, afectacion, enviar_cliente, ultimo_boleta, ultimo_factura)
 *   POST accion=emitir                      (ingreso, tipo 1 factura | 2 boleta, tipo_doc, doc, nombre, direccion, email)
 *   POST accion=reintentar                  (id + datos del cliente corregidos)
 *   POST accion=consultar | anular          (id; anular también motivo)
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$configuracion = new PdoConfiguracionFacturacion($pdo);
$entero = static fn (string $c): int => preg_match('/^\d{1,9}$/', (string) ($_POST[$c] ?? '')) === 1 ? (int) $_POST[$c] : 0;
$texto = static fn (string $c): string => trim((string) ($_POST[$c] ?? ''));

try {
    $cifrado = Cifrado::desdeClave(config('APP_CLAVE_CIFRADO'));
} catch (\RuntimeException $e) {
    error_log($e->getMessage());
    responder_error(503, 'La facturación electrónica aún no está lista en el servidor (falta la clave de cifrado). Avisa al soporte.');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'configuracion' => $configuracion->resumen(),
        'pendientes' => EmisionComprobantes::pendientes($pdo),
        'emitidos' => EmisionComprobantes::historial($pdo),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$accion = $texto('accion');
try {
    if ($accion === 'configurar') {
        $hosts = array_values(array_filter(array_map('trim', explode(',', (string) config('NUBEFACT_HOSTS_PERMITIDOS', '')))));
        $configuracion->guardar(array_map('strval', array_intersect_key($_POST, array_flip(
            ['ruta', 'token', 'serie_boleta', 'serie_factura', 'afectacion', 'enviar_cliente', 'ultimo_boleta', 'ultimo_factura']
        ))), $cifrado, $hosts);
        echo json_encode(['codigo' => 1, 'mensaje' => 'Configuración de Nubefact guardada.', 'configuracion' => $configuracion->resumen()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $cuenta = $configuracion->cargar($cifrado) ?? throw new InvalidArgumentException('Primero configura la ruta y el token de tu cuenta de Nubefact.');
    $emision = new EmisionComprobantes($pdo, new HttpClienteNubefact(), $cuenta);
    $cliente = ['tipo_doc' => $texto('tipo_doc'), 'doc' => $texto('doc'), 'nombre' => $texto('nombre'), 'direccion' => $texto('direccion'), 'email' => $texto('email')];
    $resultado = match ($accion) {
        'emitir' => $emision->emitir($entero('ingreso'), $entero('tipo'), $cliente, isset($_SESSION['S_ID']) ? (int) $_SESSION['S_ID'] : null),
        'reintentar' => $emision->reintentar($entero('id'), $cliente),
        'consultar' => $emision->consultar($entero('id')),
        'anular' => $emision->anular($entero('id'), $texto('motivo')),
        default => throw new InvalidArgumentException('Acción desconocida.'),
    };
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    responder_error(422, $e->getMessage());
} catch (RuntimeException $e) {
    error_log('Facturación: ' . $e->getMessage());
    responder_error(422, 'No se pudo leer el token guardado: vuelve a ingresarlo en la configuración.');
}
