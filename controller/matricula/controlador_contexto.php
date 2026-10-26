<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_matriculas.php';
// Matrícula paso a paso (Fase 13): aulas con vacantes, pensiones y montos del año; y, si se indica, los datos del estudiante
$MMAT = new Modelo_Matriculas();
// anio = 0: el año escolar activo (el asistente empieza ahí)
$anio = entero_post('anio', 0, 'año escolar') ?: (int) (new conexionBD())->conexionPDO()
    ->query("SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1")->fetchColumn();
if (!$anio) {
    denegar(400, 'No hay un año escolar activo');
}
$datos = ['anio' => $anio] + $MMAT->Contexto_Anio($anio);
if (isset($_POST['estu']) && $_POST['estu'] !== '') {
    $datos['estudiante'] = $MMAT->Contexto_Estudiante(entero_post('estu', 1, 'estudiante'), $anio);
    if (!$datos['estudiante']) {
        denegar(404, 'No se encontró al estudiante');
    }
}
responder_json($datos);
