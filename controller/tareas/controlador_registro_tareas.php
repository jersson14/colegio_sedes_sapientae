<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// Documentos y videos se revisan antes de guardar nada
validar_documentos_subidos('archivos', true);
$enlaces = enlaces_video_desde_texto($_POST['enlaces'] ?? '');

// DATOS DE LA TAREA
$asig = strtoupper(htmlspecialchars($_POST['asig'] ?? '', ENT_QUOTES, 'UTF-8'));
$tema = strtoupper(htmlspecialchars($_POST['tema'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'] ?? '', ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'] ?? '', ENT_QUOTES, 'UTF-8'));

$hay_archivos = isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0]);
if (!$hay_archivos && $enlaces === null) {
    denegar(400, 'Adjunte al menos un archivo o un enlace de video');
}
// El docente solo publica tareas en sus cursos
if (rol_actual() === 'DOCENTE' && !$MTA->Curso_Es_Del_Docente($asig, usuario_actual_id())) {
    registrar_acceso_denegado('controller/tareas/controlador_registro_tareas.php', 'CURSO_AJENO');
    denegar(403, 'Ese curso no está asignado a usted');
}

// Carpeta nueva y exclusiva para el material de esta tarea
$carpeta = crear_carpeta_tarea();

// Manejo de archivos
if ($hay_archivos) {
    $archivos = $_FILES['archivos'];
    for ($i = 0; $i < count($archivos['name']); $i++) {
        $nombrearchivo = strtoupper(nombre_documento_seguro($archivos['name'][$i]));
        if ($archivos['name'][$i] !== '' && !move_uploaded_file($archivos['tmp_name'][$i], $carpeta . '/' . $nombrearchivo)) {
            denegar(400, 'Error al guardar el archivo: ' . htmlspecialchars($archivos['name'][$i], ENT_QUOTES, 'UTF-8'));
        }
    }
}

// Registrar la tarea en la base de datos (se guarda la ruta de la carpeta)
$consulta = $MTA->Registrar_Tarea($asig, $tema, $fecha, $descrip, $carpeta);
if ($consulta == 1) {
    $MTA->Guardar_Enlaces_Video($carpeta, $enlaces);
} else {
    // tarea repetida: no dejar la carpeta sin uso
    array_map('unlink', glob($carpeta . '/*') ?: []);
    @rmdir($carpeta);
}

echo $consulta;
?>
