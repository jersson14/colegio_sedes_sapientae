<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    responder_json($MC->Anular_Transferencia(entero_post('id', 1, 'registro'), strtoupper(texto_post('motivo', 255, true, 'motivo')), usuario_actual_id()));
?>
