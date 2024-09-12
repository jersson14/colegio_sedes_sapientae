<?php
// Asegurarse de que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que se hayan enviado registros
if (!isset($_POST['registros'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron registros']);
    exit;
}

require '../../model/model_notas.php';
$MNOTAS = new Modelo_Notas();

// Decodificar los registros enviados desde el frontend
$registros = json_decode($_POST['registros'], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$respuesta = 1; // Inicialmente asumimos éxito
$errores = [];
$actualizaciones_exitosas = 0;

foreach ($registros as $registro) {
    if (!isset($registro['id_nota_papa'], $registro['criterio'], $registro['nota'])) {
        $errores[] = 'Registro incompleto';
        continue;
    }

    $id_nota_papa = filter_var($registro['id_nota_papa'], FILTER_VALIDATE_INT);
    $criterio = filter_var($registro['criterio'], FILTER_SANITIZE_STRING);
    $nota = filter_var($registro['nota'], FILTER_SANITIZE_STRING);

    if ($id_nota_papa === false) {
        $errores[] = 'ID de nota inválido';
        continue;
    }

    $resultado = $MNOTAS->Editar_Nota_Papas($id_nota_papa, $criterio, $nota);
    
    if (!$resultado['success']) {
        $errores[] = "Error al actualizar la nota con ID: $id_nota_papa. Razón: " . $resultado['message'];
        $respuesta = 2;
    } else {
        $actualizaciones_exitosas++;
    }
}

// Preparar la respuesta
$response = [
    'status' => $respuesta,
    'message' => $respuesta == 1 ? "Todas las notas se actualizaron correctamente, tanto las notas del estudiante como de los padres" : 
                                   "Hubo errores al actualizar algunas notas ($actualizaciones_exitosas actualizaciones exitosas)",
    'errores' => $errores
];

// Enviar la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);