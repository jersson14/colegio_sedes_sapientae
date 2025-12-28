<?php
/**
 * Controlador para listar solicitudes de información
 */
require_once '../model/model_solicitudes.php';

$solicitud_model = new Solicitud_Model();

// Obtener filtro de estado si existe (acepta GET o POST)
$estado = isset($_REQUEST['estado']) ? $_REQUEST['estado'] : null;

// Listar solicitudes
$solicitudes = $solicitud_model->Listar_Solicitudes($estado);

// Retornar como JSON en formato DataTables
header('Content-Type: application/json');
echo json_encode(array("data" => $solicitudes));
?>
