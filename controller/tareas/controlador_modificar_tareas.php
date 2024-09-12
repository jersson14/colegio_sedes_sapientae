<?php
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// DATOS DE LA TAREA
$id = strtoupper(htmlspecialchars($_POST['id'] ?? '', ENT_QUOTES, 'UTF-8'));
$asig = strtoupper(htmlspecialchars($_POST['asig'] ?? '', ENT_QUOTES, 'UTF-8'));
$tema = strtoupper(htmlspecialchars($_POST['tema'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'] ?? '', ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'] ?? '', ENT_QUOTES, 'UTF-8'));
$archivoactual = htmlspecialchars($_POST['archivoactual'] ?? '', ENT_QUOTES, 'UTF-8');

// Crear una carpeta única para este conjunto de archivos
$timestamp = time();
$carpeta = 'controller/tareas/documentos/' . $timestamp;
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Verificar si hay archivos enviados
if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
    $archivos = $_FILES['archivos'];
    $nombres_archivos = $archivos['name'];
    $archivos_temp = $archivos['tmp_name'];
    $total_archivos = count($nombres_archivos);

    // Eliminar archivos existentes en la carpeta actual
    if (!empty($archivoactual)) {
        $carpeta_anterior = 'controller/tareas/documentos/' . basename($archivoactual);

        // Eliminar todos los archivos dentro de la carpeta anterior
        if (is_dir($carpeta_anterior)) {
            $archivos_anteriores = glob($carpeta_anterior . '/*'); // Obtener todos los archivos en la carpeta

            foreach ($archivos_anteriores as $archivo) {
                if (is_file($archivo)) {
                    unlink($archivo); // Eliminar archivo
                }
            }
            rmdir($carpeta_anterior); // Eliminar la carpeta después de que esté vacía
        }
    }

    // Mover nuevos archivos a la nueva carpeta
    for ($i = 0; $i < $total_archivos; $i++) {
        $nombrearchivo = strtoupper(htmlspecialchars($nombres_archivos[$i], ENT_QUOTES, 'UTF-8'));
        if ($nombrearchivo != "") {
            $ruta = $carpeta . '/' . $nombrearchivo;
            if (!move_uploaded_file($archivos_temp[$i], $ruta)) {
                // Error al mover el archivo
                echo "Error al mover el archivo: " . $nombrearchivo;
                exit;
            }
        }
    }

    // Solo necesitamos la ruta de la carpeta, no las rutas de los archivos individuales
    $ruta_carpeta = $carpeta;
} else {
    // Si no hay archivos, mantenemos la ruta anterior
    $ruta_carpeta = $archivoactual;
}

// Actualizar tarea en la base de datos con la ruta de la carpeta
$consulta = $MTA->Modificar_Tarea($id, $asig, $tema, $fecha, $descrip, $ruta_carpeta); // Pasar la ruta de la carpeta

echo $consulta;
?>
