<?php
/**
 * Común a las pantallas del estudiante (Mis tareas, Mis notas, Mis pagos, Mi asistencia): exige el rol ESTUDIANTE y
 * elige su matrícula del año pedido (POST anio) o la del año actual. Deja $ME (modelo), $MATRICULAS y $MAT (o null).
 */
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../model/model_estudiante.php';
if (rol_actual() !== 'ESTUDIANTE') {
    denegar(403, 'Esta pantalla es para estudiantes');
}
$ME = new Modelo_Estudiante();
$MATRICULAS = $ME->Matriculas(usuario_actual_id());
$MAT = $ME->Elegir($MATRICULAS, (int) ($_POST['anio'] ?? 0));

/** Respuesta común: años para el selector y la matrícula elegida, más los datos de la pantalla. */
function estudiante_responder(array $datos) {
    global $MATRICULAS, $MAT;
    $anios = array_map(function ($m) { return ['id' => $m['anio_id'], 'anio' => $m['anio'], 'estado' => $m['estado']]; }, $MATRICULAS);
    responder_json(['ok' => true, 'anios' => $anios, 'matricula' => $MAT] + $datos);
}
