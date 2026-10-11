<?php

declare(strict_types=1);

/**
 * API de Claude de mentira para las pruebas E2E del asistente: php -S 127.0.0.1:8096 tests/E2E/claude_falso.php
 * (con IA_BASE_URL=http://127.0.0.1:8096 y ANTHROPIC_API_KEY=clave-de-prueba en colegio.env).
 *
 * Valida lo que manda la aplicación (clave, beta de fallbacks, sistema, herramientas) y responde como Messages API:
 * - primer turno: pide la primera herramienta que recibió (sin parámetros);
 * - con el tool_result: responde con las herramientas que tenía el rol y los datos recibidos, para que la prueba
 *   compruebe qué ve cada rol;
 * - si la pregunta contiene «RECHAZAR»: stop_reason refusal.
 */

header('Content-Type: application/json');
$error = static function (int $codigo, string $mensaje): never {
    http_response_code($codigo);
    exit(json_encode(['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => $mensaje]]));
};
if (($_SERVER['HTTP_X_API_KEY'] ?? '') !== 'clave-de-prueba') {
    $error(401, 'invalid x-api-key');
}
$cuerpo = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($cuerpo) || !is_array($cuerpo['messages'] ?? null) || !is_array($cuerpo['tools'] ?? null) || !is_array($cuerpo['system'] ?? null)) {
    $error(400, 'Faltan messages, tools o system.');
}
if (!str_contains((string) ($_SERVER['HTTP_ANTHROPIC_BETA'] ?? ''), 'server-side-fallback-2026-07-01') || ($cuerpo['fallbacks'] ?? null) !== 'default') {
    $error(400, 'Se esperaban los fallbacks del servidor.');
}
foreach ($cuerpo['tools'] as $herramienta) {
    if (!is_string($herramienta['name'] ?? null) || ($herramienta['input_schema']['type'] ?? '') !== 'object') {
        $error(400, 'Herramienta mal formada.');
    }
}
$nombres = array_column($cuerpo['tools'], 'name');
$ultimo = end($cuerpo['messages']);
$texto = static fn (array $m): string => is_string($m['content']) ? $m['content'] : '';
$respuesta = static fn (array $contenido, string $parada): never => exit(json_encode([
    'id' => 'msg_falso', 'type' => 'message', 'role' => 'assistant', 'model' => $cuerpo['model'] ?? 'falso',
    'content' => $contenido, 'stop_reason' => $parada, 'stop_sequence' => null,
    'usage' => ['input_tokens' => 120, 'output_tokens' => 30],
], JSON_UNESCAPED_UNICODE));

if (is_array($ultimo['content'] ?? null) && ($ultimo['content'][0]['type'] ?? '') === 'tool_result') {
    $datos = (string) ($ultimo['content'][0]['content'] ?? '');
    $respuesta([['type' => 'text', 'text' => 'Herramientas: ' . implode(',', $nombres) . "\nDatos: " . mb_substr($datos, 0, 3000)]], 'end_turn');
}
if (str_contains($texto($ultimo), 'RECHAZAR')) {
    $respuesta([], 'refusal');
}
$respuesta([
    ['type' => 'text', 'text' => 'Voy a consultar.'],
    ['type' => 'tool_use', 'id' => 'toolu_falso_1', 'name' => $nombres[0] ?? 'ninguna', 'input' => new stdClass()],
], 'tool_use');
