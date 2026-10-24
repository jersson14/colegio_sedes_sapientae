<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_contabilidad.php';
    $MC = new Modelo_Contabilidad();
    $cuenta = entero_post('cuenta', 1, 'caja');
    $fecha = fecha_post('fecha', 'fecha');
    $contado = monto_post('contado', 0, 999999999999.99, 'monto contado');
    $obs = strtoupper(texto_post('observacion', 255, false, 'observación'));
    $ajustar = !empty($_POST['ajustar']) ? 1 : 0;
    responder_json($MC->Cerrar_Caja($cuenta, $fecha, $contado, $obs, $ajustar, usuario_actual_id()));
?>
