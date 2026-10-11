<?php

declare(strict_types=1);

/**
 * Nubefact de mentira para las pruebas E2E: php -S 127.0.0.1:8097 tests/E2E/nubefact_falso.php
 * (un servidor aparte: el de la aplicación atiende una petición a la vez y no puede llamarse a sí mismo).
 *
 * - Token válido: «token-de-prueba». Cualquier ruta.
 * - generar_comprobante: comprueba que el JSON cuadre (totales e ítems); si el cliente se llama «RECHAZAR»,
 *   responde con errors como Nubefact. Las facturas salen aceptadas por la SUNAT; las boletas, solo emitidas.
 * - consultar_comprobante: aceptado si existe; si no, errors. generar_anulacion: aceptada.
 * El estado se guarda en un archivo temporal para poder consultar después (sin rechazar números repetidos:
 * cada ejecución de los flujos vuelve a empezar la numeración con datos recién cargados).
 */

header('Content-Type: application/json; charset=utf-8');
$responder = static function (array $datos, int $codigo = 200): never {
    http_response_code($codigo);
    exit(json_encode($datos, JSON_UNESCAPED_UNICODE));
};
if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Token token="token-de-prueba"') {
    $responder(['errors' => 'El token no es válido.', 'codigo' => 10], 401);
}
$cuerpo = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($cuerpo)) {
    $responder(['errors' => 'JSON inválido.', 'codigo' => 11], 400);
}
$archivo = sys_get_temp_dir() . '/nubefact_falso.json';
$emitidos = is_file($archivo) ? (array) json_decode((string) file_get_contents($archivo), true) : [];
$clave = ($cuerpo['tipo_de_comprobante'] ?? '') . '-' . ($cuerpo['serie'] ?? '') . '-' . ($cuerpo['numero'] ?? '');
$aceptado = static fn (array $c): array => [
    'tipo_de_comprobante' => $c['tipo_de_comprobante'], 'serie' => $c['serie'], 'numero' => $c['numero'],
    'enlace' => "https://www.nubefact.com/cpe/falso-{$c['serie']}-{$c['numero']}",
    'aceptada_por_sunat' => (int) $c['tipo_de_comprobante'] === 1,
    'sunat_description' => (int) $c['tipo_de_comprobante'] === 1 ? "La Factura numero {$c['serie']}-{$c['numero']}, ha sido aceptada" : null,
    'enlace_del_pdf' => "https://www.nubefact.com/cpe/falso-{$c['serie']}-{$c['numero']}.pdf",
    'enlace_del_xml' => "https://www.nubefact.com/cpe/falso-{$c['serie']}-{$c['numero']}.xml",
    'codigo_hash' => substr(sha1($c['serie'] . $c['numero']), 0, 28),
];

switch ($cuerpo['operacion'] ?? '') {
    case 'generar_comprobante':
        $item = $cuerpo['items'][0] ?? [];
        $suma = (float) ($cuerpo['total_gravada'] ?: 0) + (float) ($cuerpo['total_exonerada'] ?: 0) + (float) ($cuerpo['total_inafecta'] ?: 0) + (float) $cuerpo['total_igv'];
        if (abs($suma - (float) $cuerpo['total']) > 0.01 || abs((float) $item['total'] - (float) $cuerpo['total']) > 0.01
            || abs((float) $item['precio_unitario'] * (int) $item['cantidad'] - (float) $item['total']) > 0.01) {
            $responder(['errors' => 'Los totales no cuadran.', 'codigo' => 20]);
        }
        if (($cuerpo['cliente_denominacion'] ?? '') === 'RECHAZAR') {
            $responder(['errors' => 'El campo cliente_numero_de_documento no es válido.', 'codigo' => 21]);
        }
        $emitidos[$clave] = $cuerpo;
        file_put_contents($archivo, json_encode($emitidos));
        $responder($aceptado($cuerpo));
        // no break: $responder termina
    case 'consultar_comprobante':
        if (!isset($emitidos[$clave])) {
            $responder(['errors' => 'El documento no existe.', 'codigo' => 24]);
        }
        $responder(['aceptada_por_sunat' => true, 'sunat_description' => 'Aceptado en el resumen diario'] + $aceptado($emitidos[$clave]));
        // no break
    case 'generar_anulacion':
        $responder(['numero' => 1, 'enlace' => 'https://www.nubefact.com/anulacion/falsa', 'sunat_ticket_numero' => '1', 'aceptada_por_sunat' => false]);
        // no break
    default:
        $responder(['errors' => 'Operación desconocida.', 'codigo' => 12]);
}
