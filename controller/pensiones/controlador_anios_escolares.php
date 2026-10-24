<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();
    responder_json(['ok' => true, 'data' => $MPE->Anios_Escolares()]);
?>
