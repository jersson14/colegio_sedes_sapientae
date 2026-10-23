<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_formas_pago.php';
    $MFP = new Modelo_Formas_Pago();
    responder_json(['ok' => true, 'metodos' => $MFP->Listar_Metodos(), 'cuentas' => $MFP->Listar_Cuentas()]);
?>
