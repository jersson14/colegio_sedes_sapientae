<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    responder_json(['ok' => true, 'data' => $MC->Saldos()]);
?>
