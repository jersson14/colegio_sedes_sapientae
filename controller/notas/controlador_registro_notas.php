<?php
require '../../model/model_notas.php';
$MNOTAS = new Modelo_Notas();

if (isset($_POST['registros'])) {
    $registros = json_decode($_POST['registros'], true); // Decodificar JSON a un array PHP
    if (is_array($registros)) { // Verificar que $registros es un array válido
        // Convertir el array a JSON
        $registros_json = json_encode($registros);

        // Llamar al modelo para registrar notas
        $resultado = $MNOTAS->Registrar_Notas($registros_json);

        if ($resultado == 2) {
            echo json_encode(["inserted_count" => 0, "status" => 2]); // Algunos registros ya existen
        } elseif ($resultado == 1) {
            echo json_encode(["inserted_count" => 1, "status" => 1]); // Todos los registros fueron insertados con éxito
        } else {
            echo json_encode(["inserted_count" => 0, "status" => 0]); // Error en la inserción o datos incompletos
        }
    } else {
        echo json_encode(["inserted_count" => 0, "status" => 0]); // Error debido a datos incompletos o formato incorrecto
    }
} else {
    echo json_encode(["inserted_count" => 0, "status" => 0]); // Error: No se recibieron registros
}
?>
