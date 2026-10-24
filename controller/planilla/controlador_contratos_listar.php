<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    responder_json(['ok' => true, 'data' => $MP->Contratos(tipo_personal_post()), 'tipos' => $MP->Tipos_Contrato()]);
?>
