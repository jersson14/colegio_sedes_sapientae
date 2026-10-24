<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    $cuenta = entero_post('cuenta', 1, 'cuenta');
    $desde = fecha_post('desde', 'fecha desde');
    $hasta = fecha_post('hasta', 'fecha hasta');
    if ($desde > $hasta) { denegar(400, 'La fecha desde debe ser anterior a la fecha hasta'); }
    responder_json(['ok' => true] + $MC->Movimientos($cuenta, $desde, $hasta));
?>
