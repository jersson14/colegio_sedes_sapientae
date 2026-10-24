<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_reportes.php';
    $MR = new Modelo_Reportes();
    responder_json(['ok' => true] + $MR->Mensual(entero_post('anio', 2000, 'año')));
?>
