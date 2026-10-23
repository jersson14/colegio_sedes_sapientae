<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension();
    // Saldo de una pensión para una matrícula: precio, mora aplicable, lo pagado y lo pendiente
    $saldo = $MPP->Saldo_Pension(entero_post('id_matri', 1, 'matrícula'), entero_post('id_pension', 1, 'pensión'));
    responder_json($saldo ? ['ok' => true, 'data' => $saldo] : ['ok' => false, 'mensaje' => 'Pensión no encontrada']);
?>
