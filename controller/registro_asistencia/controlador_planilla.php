<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/planilla_asistencia.php';
// Planilla mensual de asistencia de un aula (estudiantes × días del mes) con sus marcas
$datos = planilla_datos((int) ($_POST['anio'] ?? 0), (int) ($_POST['aula'] ?? 0), $_POST['mes'] ?? '');
foreach ($datos['estudiantes'] as &$e) {
    $e['totales'] = planilla_totales($e['marcas']);
}
unset($e);
responder_json($datos);
