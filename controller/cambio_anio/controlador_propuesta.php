<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_cambio_anio.php';
// Cambio de año escolar (Fase 13): años, lo que ya tiene el año nuevo y la propuesta de promoción por aula.
// Sin años: el que termina es el activo y el nuevo, el siguiente por número.
$M = new Modelo_Cambio_Anio();
$anios = $M->Anios();
$origen = (int) ($_POST['origen'] ?? 0);
$destino = (int) ($_POST['destino'] ?? 0);
if (!$origen) {
    foreach ($anios as $a) { if ($a['estado'] === 'ACTIVO') { $origen = (int) $a['id']; } }
}
$num = null;
foreach ($anios as $a) { if ((int) $a['id'] === $origen) { $num = (int) $a['anio']; } }
if ($num === null) {
    denegar(400, 'Año escolar que termina no encontrado');
}
if (!$destino) {
    foreach ($anios as $a) { if ((int) $a['anio'] === $num + 1) { $destino = (int) $a['id']; } }
}
if ($destino && $destino === $origen) {
    denegar(400, 'El año nuevo debe ser distinto del que termina');
}
$datos = ['anios' => $anios, 'origen' => $origen, 'destino' => $destino ?: null];
if ($destino) {
    $datos['ya_tiene'] = $M->Destino($destino);
    $datos += $M->Propuesta($origen, $destino);
}
responder_json($datos);
