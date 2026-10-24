<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    // Solo ADMINISTRADOR (ver controller/_permisos.php)
    responder_json($MP->Aprobar(entero_post('id', 1, 'planilla'), usuario_actual_id()));
?>
