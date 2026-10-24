<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_planilla.php';
    $MP = new Modelo_Planilla();
    // Docentes activos con lo registrado ese día (o las horas de su horario para proponer)
    responder_json(['ok' => true, 'data' => $MP->Asistencia_Dia(fecha_post('fecha', 'fecha'))]);
?>
