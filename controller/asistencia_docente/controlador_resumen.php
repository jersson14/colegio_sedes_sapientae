<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    $mes = entero_post('mes', 1, 'mes');
    if ($mes > 12) { denegar(400, 'Dato inválido: mes'); }
    responder_json(['ok' => true, 'data' => $MP->Resumen_Asistencia(entero_post('anio', 2000, 'año'), $mes)]);
?>
