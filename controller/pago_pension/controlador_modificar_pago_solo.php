<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_pago_pension.php';
$MPP = new Modelo_Pago_Pension();
    $id = entero_post('id', 1, 'pago');
    $monto = monto_post('monto', 0.01, 999999999999.99, 'monto');
    $descrip = strtoupper(texto_post('descrip', 255, true, 'motivo de la modificación'));
    responder_json($MPP->Modificar_Pago_pension($id, $monto, $descrip, usuario_actual_id()));
?>
