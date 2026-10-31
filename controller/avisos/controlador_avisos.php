<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_avisos.php';
// Avisos a las familias (Fase 13): accion=listar | marcar | configurar.
// Cada rol ve lo suyo: el auxiliar faltas, tareas y comunicados; la contadora, pensiones; la dirección, todo.
// Solo los tipos encendidos en la configuración.
$M = new Modelo_Avisos();
$cfg = $M->Config();
$por_rol = ['ADMINISTRADOR' => Modelo_Avisos::TIPOS, 'AUXILIAR' => ['FALTA', 'TAREA', 'COMUNICADO'], 'CONTADOR' => ['PENSION']];
$mios = $por_rol[rol_actual()] ?? denegar(403, 'Acceso denegado');
$activos = array_values(array_filter($mios, function ($t) use ($cfg) { return ($cfg[strtolower($t)] ?? '1') === '1'; }));
$accion = $_POST['accion'] ?? 'listar';

if ($accion === 'configurar') {
    if (rol_actual() !== 'ADMINISTRADOR') {
        denegar(403, 'Solo la dirección configura los avisos');
    }
    $nuevo = [];
    foreach (['falta', 'tarea', 'pension', 'comunicado'] as $k) { $nuevo[$k] = !empty($_POST[$k]) ? '1' : '0'; }
    $_POST['dias_pension'] = $_POST['dias_pension'] ?? '3';
    $nuevo['dias_pension'] = (string) entero_post('dias_pension', 0, 'días de anticipación');
    if ((int) $nuevo['dias_pension'] > 30) {
        denegar(400, 'Días de anticipación: máximo 30');
    }
    $M->Guardar_Config($nuevo);
    responder_json(['ok' => true, 'config' => $M->Config()]);
}

if ($accion === 'marcar') {
    $tipo = strtoupper((string) ($_POST['tipo'] ?? ''));
    if (!in_array($tipo, $activos, true)) {
        denegar(403, 'Ese aviso no corresponde a su rol o está apagado');
    }
    $ref = texto_post('referencia', 40, true, 'referencia');
    $mat = entero_post('matricula', 1, 'matrícula');
    $destino = preg_replace('/[^0-9+]/', '', texto_post('destino', 30, false, 'celular'));
    responder_json(['ok' => true, 'nuevo' => $M->Marcar($tipo, $ref, $mat, $destino, usuario_actual_id()) > 0]);
}

$fecha = trim((string) ($_POST['fecha'] ?? '')) === '' ? date('Y-m-d') : fecha_post('fecha', 'fecha');
$pedidos = array_intersect($activos, array_map('strtoupper', (array) ($_POST['tipos'] ?? $activos)));
responder_json([
    'ok' => true, 'fecha' => $fecha, 'tipos' => $activos, 'todos_los_tipos' => $mios, 'config' => $cfg,
    'puede_configurar' => rol_actual() === 'ADMINISTRADOR',
    'avisos' => $M->Pendientes(array_values($pedidos), $fecha, !empty($_POST['con_enviados']), (int) $cfg['dias_pension']),
]);
