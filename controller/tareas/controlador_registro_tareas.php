<?php
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// DATOS DE REMITENTE
$asig = strtoupper(htmlspecialchars($_POST['asig'], ENT_QUOTES, 'UTF-8'));
$tema = strtoupper(htmlspecialchars($_POST['tema'], ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'], ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'], ENT_QUOTES, 'UTF-8'));

// Crear una carpeta única para este conjunto de archivos
$timestamp = time();
$carpeta = 'controller/tareas/documentos/tarea_alumnos_' . $timestamp; // Cambié la ruta base
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Manejo de archivos
if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
    $archivos = $_FILES['archivos'];
    $total_archivos = count($archivos['name']);
    $rutas = [];

    for ($i = 0; $i < $total_archivos; $i++) {
        $nombrearchivo = strtoupper(htmlspecialchars($archivos['name'][$i], ENT_QUOTES, 'UTF-8'));
        if ($nombrearchivo != "") {
            $ruta = $carpeta . '/' . $nombrearchivo;
            if (move_uploaded_file($archivos['tmp_name'][$i], $ruta)) {
                $rutas[] = $ruta; // Añadir ruta del archivo al array
            } else {
                // Error al mover el archivo
                echo "Error al mover el archivo: " . $archivos['name'][$i];
                exit;
            }
        }
    }

    // Convertir el array de rutas a una cadena separada por comas
    $rutas_str = implode(',', $rutas);
} else {
    $rutas_str = ''; // Si no hay archivos, dejar como vacío o manejar como prefieras
}

// Registrar la tarea en la base de datos
$consulta = $MTA->Registrar_Tarea($asig, $tema, $fecha, $descrip, $carpeta); // Pasar solo la ruta de la carpeta

echo $consulta;
?>
