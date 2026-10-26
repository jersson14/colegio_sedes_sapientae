<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_ficha.php';
// Ficha única del estudiante (Fase 13). Cada rol ve solo las secciones de su trabajo.
$secciones = [
    'ADMINISTRADOR' => ['datos', 'padres', 'asistencia', 'notas', 'tareas', 'pensiones', 'salud'],
    'AUXILIAR'      => ['datos', 'padres', 'asistencia', 'notas', 'tareas'],
    'CONTADOR'      => ['datos', 'padres', 'pensiones'],
][rol_actual()] ?? denegar(403, 'Acceso denegado');
$id = entero_post('id', 1, 'estudiante');
$matricula = isset($_POST['matricula']) && $_POST['matricula'] !== '' ? entero_post('matricula', 1, 'matrícula') : null;
$M = new Modelo_Ficha();
$ficha = $M->Ficha($id, $secciones, $matricula);
if (!$ficha) {
    denegar(404, 'No se encontró al estudiante');
}
responder_json($ficha);
