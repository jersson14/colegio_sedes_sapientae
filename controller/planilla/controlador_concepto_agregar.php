<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    responder_json($MP->Agregar_Concepto(entero_post('id', 1, 'detalle'), entero_post('concepto', 1, 'concepto'), monto_post('valor', 0.01, 999999999999.99, 'valor')));
?>
