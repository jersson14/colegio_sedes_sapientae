<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// DATOS DE LA TAREA
$iddetalle = strtoupper(htmlspecialchars($_POST['iddetalle'] ?? '', ENT_QUOTES, 'UTF-8'));
$archivoactual = htmlspecialchars($_POST['archivoactual'] ?? '', ENT_QUOTES, 'UTF-8');

// Un estudiante solo puede enviar/reemplazar su propia tarea; la carpeta anterior sale de la BD
if (rol_actual() === 'ESTUDIANTE') {
    $propio = $MTA->Detalle_Tarea_Propio($iddetalle, usuario_actual_id());
    if ($propio === null) {
        denegar(403, 'No tiene permiso para esta acción');
    }
    $archivoactual = htmlspecialchars($propio, ENT_QUOTES, 'UTF-8');
}

// Crear una carpeta única para este conjunto de archivos en "tarea_alumnos"
$carpeta = crear_carpeta_tarea();

// Verificar si hay archivos enviados
if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
    validar_documentos_subidos('archivos');
    $archivos = $_FILES['archivos'];
    $nombres_archivos = $archivos['name'];
    $archivos_temp = $archivos['tmp_name'];
    $total_archivos = count($nombres_archivos);

    // Eliminar archivos existentes en la carpeta actual
    if (carpeta_tarea_valida($archivoactual) !== '') {
        $carpeta_anterior = 'controller/tareas/documentos/' . carpeta_tarea_valida($archivoactual);

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
        $nombrearchivo = strtoupper(nombre_documento_seguro($nombres_archivos[$i]));
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
$consulta = $MTA->Registrar_envio_tarea($iddetalle, $ruta_carpeta); // Pasar la ruta de la carpeta

echo $consulta;
?>
