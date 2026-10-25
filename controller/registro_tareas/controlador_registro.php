<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_registro_tareas.php';
// Registro de tareas de un curso: estudiantes × tareas con estado y nota (el docente solo ve sus cursos)
$MA = new Modelo_Registro_Asistencia();
$MT = new Modelo_Registro_Tareas();
$anios = $MA->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) {
        if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
    }
}
$docente = rol_actual() === 'DOCENTE' ? usuario_actual_id() : null;
$cursos = $MT->Cursos($anio, $docente);
$curso = null;
foreach ($cursos as $c) {
    if ((int) $c['id'] === (int) ($_POST['curso'] ?? 0)) { $curso = $c; }
}
if (!empty($_POST['curso']) && !$curso) {
    registrar_acceso_denegado('controller/registro_tareas/controlador_registro.php', 'CURSO_AJENO');
    denegar(403, 'Ese curso no está asignado a usted');
}
// por defecto el primer curso con tareas
if (!$curso) {
    foreach ($cursos as $c) {
        if ($c['tareas'] > 0) { $curso = $c; break; }
    }
    $curso = $curso ?: ($cursos[0] ?? null);
}
$periodos = $MT->Periodos($anio);
$periodo = null;
foreach ($periodos as $p) {
    if ((int) $p['id'] === (int) ($_POST['periodo'] ?? 0)) { $periodo = $p; }
}
$tareas = [];
$estudiantes = [];
if ($curso) {
    $MT->Completar_Envios($curso['id']);
    $tareas = $MT->Tareas($curso['id'], $periodo['fecha_inicio'] ?? null, $periodo['fecha_fin'] ?? null);
    $estudiantes = $MT->Estudiantes_Envios($curso['id'], array_column($tareas, 'id'));
}
responder_json([
    'anios' => $anios, 'anio' => $anio, 'cursos' => $cursos, 'curso' => $curso,
    'periodos' => $periodos, 'periodo' => $periodo['id'] ?? 0,
    'tareas' => $tareas, 'estudiantes' => $estudiantes, 'ahora' => date('Y-m-d H:i:s'),
]);
