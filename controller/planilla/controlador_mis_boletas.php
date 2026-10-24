<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    // El docente solo ve sus propias boletas (se toma el usuario de la sesión)
    responder_json(['ok' => true, 'data' => $MP->Mis_Boletas(usuario_actual_id())]);
?>
