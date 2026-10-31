<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_cambio_anio.php';
// Ejecuta el cambio de año escolar (Fase 13) en una sola transacción: copias al año nuevo, matrículas de los
// promovidos y de quienes repiten, y (opcional) activa el año nuevo.
$origen = entero_post('origen', 1, 'año que termina');
$destino = entero_post('destino', 1, 'año nuevo');
$decisiones = json_decode($_POST['decisiones'] ?? '[]', true);
if (!is_array($decisiones) || count($decisiones) > 5000) {
    denegar(400, 'Decisiones no válidas');
}
$validas = ['PROMOVER', 'REPETIR', 'EGRESA', 'NO_CONTINUA', 'LUEGO'];
$limpias = [];
foreach ($decisiones as $d) {
    $m = filter_var($d['matricula'] ?? null, FILTER_VALIDATE_INT);
    $accion = strtoupper((string) ($d['accion'] ?? ''));
    if (!$m || !in_array($accion, $validas, true)) {
        denegar(400, 'Decisión no válida');
    }
    $limpias[$m] = ['accion' => $accion, 'aula' => filter_var($d['aula'] ?? null, FILTER_VALIDATE_INT) ?: null];
}
$copiar = [];
foreach (['periodos', 'pensiones', 'cursos', 'horarios'] as $k) { $copiar[$k] = !empty($_POST['copiar_' . $k]); }
$M = new Modelo_Cambio_Anio();
try {
    responder_json(['ok' => true] + $M->Ejecutar($origen, $destino, $limpias, $copiar, !empty($_POST['activar']), usuario_actual_id()));
} catch (InvalidArgumentException $e) {
    denegar(400, $e->getMessage());
}
