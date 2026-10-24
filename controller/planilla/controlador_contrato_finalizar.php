<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    responder_json($MP->Finalizar_Contrato(entero_post('id', 1, 'contrato'), fecha_post('fin', 'fecha de fin')));
?>
