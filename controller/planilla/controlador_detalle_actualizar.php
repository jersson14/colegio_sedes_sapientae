<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    foreach (['horas', 'base'] as $k) { if (trim($_POST[$k] ?? '') === '') { $_POST[$k] = '0'; } }
    responder_json($MP->Actualizar_Detalle(entero_post('id', 1, 'detalle'), monto_post('horas', 0, 9999, 'horas'), monto_post('base', 0, 999999999999.99, 'sueldo base')));
?>
