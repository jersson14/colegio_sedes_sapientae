<?php
/**
 * Controlador para obtener estadísticas de solicitudes
 */
require_once '../model/model_solicitudes.php';

$solicitud_model = new Solicitud_Model();

try {
    $estadisticas = $solicitud_model->Obtener_Estadisticas();
    
    // Asegurar que no sea false/null
    if (!$estadisticas) {
        $estadisticas = array(
            'total' => 0,
            'pendientes' => 0,
            'contactados' => 0,
            'atendidos' => 0,
            'hoy' => 0
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(array('error' => $e->getMessage()));
}
?>
