<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'SECRETARIA', 'DOCENTE', 'ESTUDIANTE');
require_once __DIR__ . '/../../core/modulos.php';
exigir_modulo(App\Comercial\ModuloOpcional::AsistenteIa);
require_once __DIR__ . '/../../model/model_conexion.php';
require_once __DIR__ . '/../../core/marca.php';
require_once __DIR__ . '/../../core/institucion.php';

use App\Asistente\AsistenteConsultas;
use App\Asistente\ClaudeModeloAsistente;
use App\Asistente\FalloModelo;
use App\Asistente\HerramientasAsistente;
use App\Asistente\RegistroConsultasIa;
use App\Comercial\ModuloOpcional;

/**
 * Asistente de IA (módulo opcional; el consumo lo paga la plataforma).
 *   GET                              → consultas usadas este mes y su límite
 *   POST pregunta, historial (JSON)  → respuesta; 429 si se pasó el límite mensual o el del usuario por hora
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$registro = new RegistroConsultasIa($pdo);
$limiteMes = modulos_institucion()->limite(ModuloOpcional::AsistenteIa);
$usuId = (int) ($_SESSION['S_ID'] ?? 0);
$rol = (string) ($_SESSION['S_ROL'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['usadas_mes' => $registro->delMes(), 'limite_mes' => $limiteMes], JSON_UNESCAPED_UNICODE);
    exit;
}

$pregunta = trim((string) ($_POST['pregunta'] ?? ''));
if ($pregunta === '' || mb_strlen($pregunta) > 500) {
    responder_error(422, 'Escribe una pregunta (hasta 500 caracteres).');
}
if ($limiteMes !== null && $registro->delMes() >= $limiteMes) {
    responder_error(429, 'La institución alcanzó el límite de consultas del asistente de este mes.');
}
if ($registro->delUsuarioEnLaUltimaHora($usuId) >= (int) config('IA_LIMITE_USUARIO_HORA', '20')) {
    responder_error(429, 'Hiciste muchas consultas en la última hora; intenta más tarde.');
}
$clave = (string) config('ANTHROPIC_API_KEY', '');
if ($clave === '') {
    error_log('Asistente de IA: falta ANTHROPIC_API_KEY en colegio.env.');
    responder_error(503, 'El asistente aún no está listo en el servidor. Avisa al soporte.');
}

// El SDK de Anthropic viene de Composer; el resto del sistema no depende de vendor/ (core/autoload.php).
$composer = __DIR__ . '/../../vendor/autoload.php';
if (!is_file($composer)) {
    error_log('Asistente de IA: falta vendor/ (composer install --no-dev).');
    responder_error(503, 'El asistente aún no está listo en el servidor. Avisa al soporte.');
}
require_once $composer;
$cliente = new Anthropic\Client(apiKey: $clave, baseUrl: config('IA_BASE_URL') ?: null);
$asistente = new AsistenteConsultas(
    new ClaudeModeloAsistente($cliente, (string) config('IA_MODELO', 'claude-opus-5-5'), (string) config('IA_ESFUERZO', 'medium')),
    new HerramientasAsistente($pdo, $rol, $usuId, institucion_config()),
);
$historial = AsistenteConsultas::historial(json_decode((string) ($_POST['historial'] ?? '[]'), true));
try {
    $r = $asistente->preguntar(AsistenteConsultas::sistema(marca()->nombre, $rol, date('Y-m-d')), $pregunta, $historial);
} catch (FalloModelo $e) {
    error_log($e->getMessage());
    $registro->registrar($usuId, $rol, $pregunta, [], 0, 0, 'ERROR');
    responder_error(502, 'El asistente no respondió; intenta de nuevo en un momento.');
}
$registro->registrar($usuId, $rol, $pregunta, $r['herramientas'], $r['tokens_entrada'], $r['tokens_salida'], $r['estado']);
echo json_encode(['respuesta' => $r['respuesta'], 'usadas_mes' => $registro->delMes(), 'limite_mes' => $limiteMes], JSON_UNESCAPED_UNICODE);
