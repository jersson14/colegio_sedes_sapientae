<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    responder_json($MP->Generar(entero_post('anio', 2000, 'año'), entero_post('mes', 1, 'mes'), usuario_actual_id()));
?>
