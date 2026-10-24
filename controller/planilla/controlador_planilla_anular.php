<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    responder_json($MP->Anular(entero_post('id', 1, 'planilla'), strtoupper(texto_post('motivo', 255, true, 'motivo')), usuario_actual_id()));
?>
