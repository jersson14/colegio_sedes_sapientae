<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension();
    // Métodos de pago y cajas/cuentas activas para la pantalla de cobro
    responder_json(['ok' => true] + $MPP->Listar_Formas_Pago());
?>
