<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    $fecha = trim($_POST['fecha'] ?? '') === '' ? date('Y-m-d') : fecha_post('fecha', 'fecha');
    responder_json($MP->Pagar(entero_post('id', 1, 'detalle'), monto_post('monto', 0.01, 999999999999.99, 'monto'), $fecha,
        entero_post('metodo', 1, 'forma de pago'), filter_var($_POST['cuenta'] ?? null, FILTER_VALIDATE_INT) ?: null,
        strtoupper(texto_post('referencia', 60, false, 'referencia')), usuario_actual_id()));
?>
