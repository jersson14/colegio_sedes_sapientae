<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_caja.php';
// Cobrar pensión (caja): accion=anios | buscar (estudiante por nombre o DNI) | estado (estado de cuenta de una matrícula).
// El cobro y la anulación usan controlador_detalle_pago_pension.php y controlador_eliminar_pago_pension.php.
$MC = new Modelo_Caja();
$accion = $_POST['accion'] ?? 'anios';

if ($accion === 'buscar') {
    $texto = trim((string) ($_POST['q'] ?? ''));
    if (mb_strlen($texto, 'UTF-8') < 2) {
        responder_json(['ok' => true, 'estudiantes' => []]);
    }
    responder_json(['ok' => true, 'estudiantes' => $MC->Buscar(entero_post('anio', 1, 'año escolar'), $texto)]);
}

if ($accion === 'estado') {
    $matricula = entero_post('matricula', 1, 'matrícula');
    $estado = $MC->Estado($matricula);
    if (!$estado['estudiante']) {
        denegar(404, 'Matrícula no encontrada');
    }
    responder_json(['ok' => true, 'hoy' => date('Y-m-d')] + $estado);
}

$anios = (new Modelo_Registro_Asistencia())->Anios();
$activo = 0;
foreach ($anios as $a) {
    if ($a['estado'] === 'ACTIVO') { $activo = (int) $a['id']; break; }
}
responder_json(['ok' => true, 'anios' => $anios, 'anio' => $activo ?: (int) ($anios[0]['id'] ?? 0)]);
