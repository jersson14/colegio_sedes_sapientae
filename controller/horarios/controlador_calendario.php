<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/parametros.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_aula_virtual.php';
// Calendario de horarios (semana y mes). Dirección y auxiliar: cualquier aula. Docente: sus clases (modo "mio")
// o el horario completo de un aula donde enseña. Estudiante: el horario de su aula.
$MA = new Modelo_Registro_Asistencia();
$M = new Modelo_Aula_Virtual();
$anios = $MA->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) {
        if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
    }
}
$anio_escolar = $MA->Anio($anio);
if (!$anio_escolar) {
    denegar(400, 'Año escolar no encontrado');
}
$rol = rol_actual();
$usu = usuario_actual_id();
$aula = (int) ($_POST['aula'] ?? 0);
$modo = 'aula';
if ($rol === 'ESTUDIANTE') {
    $mat = $M->Matricula_Estudiante($usu, $anio);
    $aula = $mat ? (int) $mat['aula'] : -1;
    $aulas = array_values(array_filter($M->Aulas($anio), function ($a) use ($aula) { return (int) $a['id'] === $aula; }));
} else {
    $aulas = $M->Aulas($anio, $rol === 'DOCENTE' ? $usu : null);
    if ($aula && !in_array($aula, array_map('intval', array_column($aulas, 'id')), true)) {
        registrar_acceso_denegado('controller/horarios/controlador_calendario.php', 'AULA_AJENA');
        denegar(403, 'No enseña en esa aula');
    }
    if ($rol === 'DOCENTE' && !$aula) {
        $modo = 'mio';
    } elseif (!$aula) {
        $aula = (int) ($aulas[0]['id'] ?? 0);
    }
}
$clases = $modo === 'mio' ? $M->Clases($anio, ['docente' => $usu]) : ($aula > 0 ? $M->Clases($anio, ['aula' => $aula]) : []);
responder_json([
    'anios' => $anios, 'anio' => $anio_escolar, 'aulas' => $aulas, 'aula' => $aula, 'modo' => $modo,
    'clases' => $clases, 'sabado' => parametro('clases_sabado') === '1', 'hoy' => date('Y-m-d'),
]);
