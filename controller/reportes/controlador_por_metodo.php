<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_reportes.php';
    $MR = new Modelo_Reportes();
    [$desde, $hasta] = rango_fechas_post();
    responder_json(['ok' => true] + $MR->Por_Metodo($desde, $hasta));
?>
