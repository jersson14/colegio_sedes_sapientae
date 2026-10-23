<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension(); // Instanciamos
    // Ya no se elimina: el pago y su ingreso quedan ANULADOS con motivo y usuario
    $id = entero_post('id', 1, 'pago');
    $motivo = strtoupper(texto_post('motivo', 255, true, 'motivo de la anulación'));
    responder_json($MPP->Anular_pago_pension($id, $motivo, usuario_actual_id()));
?>
