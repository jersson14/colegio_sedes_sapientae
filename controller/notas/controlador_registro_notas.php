<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE');
    require_once __DIR__ . '/../../core/pertenencia.php';
require '../../model/model_notas.php';
$MNOTAS = new Modelo_Notas();

if (isset($_POST['registros'])) {
    $registros = json_decode($_POST['registros'], true); // Decodificar JSON a un array PHP
    if (is_array($registros)) { // Verificar que $registros es un array válido
        // IDOR: cada nota debe ir a un criterio del curso del docente y a un alumno de esa aula.
        exigir_notas_propias($registros);
        // Convertir el array a JSON
        $registros_json = json_encode($registros);

        // Llamar al modelo para registrar notas
        $resultado = $MNOTAS->Registrar_Notas($registros_json);

        // SP_REGISTRAR_NOTAS devuelve cuántas notas insertó (antes, solo el conteo del último registro,
        // así que la UI mostraba «error» si la última nota ya existía aunque las demás se guardaran).
        if ($resultado === false) {
            echo json_encode(["inserted_count" => 0, "status" => 0]); // Error en la inserción
        } elseif ((int)$resultado === count($registros)) {
            echo json_encode(["inserted_count" => (int)$resultado, "status" => 1]); // Todas insertadas
        } else {
            echo json_encode(["inserted_count" => (int)$resultado, "status" => 2]); // Algunas ya existían
        }
    } else {
        echo json_encode(["inserted_count" => 0, "status" => 0]); // Error debido a datos incompletos o formato incorrecto
    }
} else {
    echo json_encode(["inserted_count" => 0, "status" => 0]); // Error: No se recibieron registros
}
?>
