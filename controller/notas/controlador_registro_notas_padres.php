<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/regional.php';
require '../../model/model_notas.php';

$MNOTAS = new Modelo_Notas();

if (isset($_POST['registros'])) {
    $registros_json = $_POST['registros'];
    
    // Decodificar el JSON para verificar su validez
    $registros = json_decode($registros_json, true);
    
    // Cada nota debe cumplir la escala del colegio
    if (is_array($registros)) {
        foreach ($registros as &$r) {
            $r['nota'] = is_array($r) ? nota_normalizada($r['nota'] ?? '') : null;
            if ($r['nota'] === null) {
                echo json_encode(["status" => 0, "message" => 'Nota fuera de la escala: use ' . descripcion_escala()]);
                exit;
            }
        }
        unset($r);
    }
    if (is_array($registros) && !empty($registros)) {
        $resultado = $MNOTAS->Registrar_Notas_Padres($registros);
        
        if (isset($resultado['processed_count']) && $resultado['processed_count'] > 0) {
            echo json_encode([
                "status" => 1,
                "message" => "Notas de padres registradas satisfactoriamente.",
                "inserted_count" => $resultado['processed_count']
            ]);
        } else {
            echo json_encode([
                "status" => 0,
                "message" => "Error al registrar las notas de padres."
            ]);
        }
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Formato de datos incorrecto o no hay registros para procesar."
        ]);
    }
} else {
    echo json_encode([
        "status" => 0,
        "message" => "No se recibieron registros."
    ]);
}
?>