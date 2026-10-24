<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    // Depósito (caja -> banco), transferencia entre cuentas o retiro (banco -> caja)
    $tipo = strtoupper(texto_post('tipo', 20, true, 'tipo'));
    $origen = entero_post('origen', 1, 'cuenta de origen');
    $destino = entero_post('destino', 1, 'cuenta de destino');
    $monto = monto_post('monto', 0.01, 999999999999.99, 'monto');
    $fecha = trim($_POST['fecha'] ?? '') === '' ? date('Y-m-d') : fecha_post('fecha', 'fecha');
    $ref = strtoupper(texto_post('referencia', 60, false, 'referencia'));
    $obs = strtoupper(texto_post('observacion', 255, false, 'observación'));
    responder_json($MC->Registrar_Transferencia($tipo, $origen, $destino, $monto, $fecha, $ref, $obs, usuario_actual_id()));
?>
