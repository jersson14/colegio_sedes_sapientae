<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/libreta.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
// Libretas de notas de un aula (Fase 13): notas por curso, promedio y orden de mérito.
// anio = 0: el año activo; aula = 0: la primera aula con estudiantes; periodo vacío: todo el año.
$M = new Modelo_Registro_Asistencia();
$anios = $M->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) { if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; } }
    $anio = $anio ?: (int) ($anios[0]['id'] ?? 0);
}
if (!$M->Anio($anio)) {
    denegar(400, 'Año escolar no encontrado');
}
$aulas = $M->Aulas($anio);
$aula = (int) ($_POST['aula'] ?? 0);
if ($aula && !array_filter($aulas, function ($a) use ($aula) { return (int) $a['id'] === $aula; })) {
    denegar(400, 'El aula no tiene estudiantes en ese año');
}
$aula = $aula ?: (int) ($aulas[0]['id'] ?? 0);
$periodo = trim((string) ($_POST['periodo'] ?? '')) === '' ? null : entero_post('periodo', 1, 'periodo');

$libreta = $aula ? libreta_aula($anio, $aula, $periodo) : null;
if ($aula && !$libreta) {
    denegar(400, 'El periodo no pertenece a ese año escolar');
}
if ($libreta) {
    foreach ($libreta['estudiantes'] as &$e) { unset($e['detalle']); }   // el detalle por criterio va solo en el PDF
    unset($e);
}
$q = (new conexionBD())->conexionPDO()->prepare("SELECT id_periodo AS id, periodos AS nombre, fecha_inicio AS inicio, fecha_fin AS fin FROM periodos WHERE `id_año_escolar` = ? ORDER BY fecha_inicio, id_periodo");
$q->execute([$anio]);
responder_json(['anios' => $anios, 'anio' => $anio, 'aulas' => $aulas, 'aula' => $aula, 'periodos_anio' => $q->fetchAll(PDO::FETCH_ASSOC),
                'periodo' => $periodo, 'libreta' => $libreta]);
