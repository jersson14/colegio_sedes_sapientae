<?php
/**
 * Test directo del modelo
 */
require_once 'model/model_solicitudes.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test del Modelo de Solicitudes</h1>";

try {
    $solicitud_model = new Solicitud_Model();
    echo "<p>✓ Modelo creado correctamente</p>";
    
    $solicitudes = $solicitud_model->Listar_Solicitudes();
    echo "<p>✓ Método Listar_Solicitudes ejecutado</p>";
    
    echo "<p>Total de solicitudes: " . count($solicitudes) . "</p>";
    
    echo "<h2>Datos:</h2>";
    echo "<pre>";
    print_r($solicitudes);
    echo "</pre>";
    
    echo "<h2>JSON:</h2>";
    echo "<pre>";
    echo json_encode(array("data" => $solicitudes), JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
