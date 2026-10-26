<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/respaldo.php';
require '../../model/model_panel.php';
// Panel de inicio del administrador: estado del colegio hoy (año escolar, comunidad, asistencia, caja, pendientes)
$datos = (new Modelo_Panel())->Admin();
$respaldos = respaldos_listar();
$datos['ultimo_respaldo'] = $respaldos ? $respaldos[0]['fecha'] : null;
responder_json($datos);
