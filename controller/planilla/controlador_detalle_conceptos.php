<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    $det = entero_post('id', 1, 'detalle');
    responder_json(['ok' => true, 'conceptos' => $MP->Conceptos_Detalle($det), 'pagos' => $MP->Pagos($det)]);
?>
