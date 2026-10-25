<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require '../../model/model_tareas.php';
$MTA = new Modelo_Tareas(); // Instanciar el modelo

// Documentos y videos se revisan antes de cambiar nada
validar_documentos_subidos('archivos', true);
$enlaces = enlaces_video_desde_texto($_POST['enlaces'] ?? '');

// DATOS DE LA TAREA
$id = strtoupper(htmlspecialchars($_POST['id'] ?? '', ENT_QUOTES, 'UTF-8'));
$asig = strtoupper(htmlspecialchars($_POST['asig'] ?? '', ENT_QUOTES, 'UTF-8'));
$tema = strtoupper(htmlspecialchars($_POST['tema'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = strtoupper(htmlspecialchars($_POST['fecha'] ?? '', ENT_QUOTES, 'UTF-8'));
$descrip = strtoupper(htmlspecialchars($_POST['descrip'] ?? '', ENT_QUOTES, 'UTF-8'));
// Archivos actuales que el usuario marcó para quitar (nombres dentro de la carpeta de la tarea)
$quitar = json_decode($_POST['quitar'] ?? '[]', true);
$quitar = is_array($quitar) ? array_map('basename', array_filter($quitar, 'is_string')) : [];

// El docente solo modifica tareas de sus cursos y las deja en sus cursos
if (rol_actual() === 'DOCENTE' && (!$MTA->Tarea_Es_Del_Docente($id, usuario_actual_id())
        || !$MTA->Curso_Es_Del_Docente($asig, usuario_actual_id()))) {
    registrar_acceso_denegado('controller/tareas/controlador_modificar_tareas.php', 'TAREA_AJENA');
    denegar(403, 'Esa tarea no es de sus cursos');
}

// La carpeta se toma de la base (no del navegador). Los archivos nuevos se suman a los que ya tenía.
$nombre_carpeta = carpeta_tarea_valida($MTA->Carpeta_Tarea($id));
$carpeta = $nombre_carpeta === '' ? crear_carpeta_tarea() : 'controller/tareas/documentos/' . $nombre_carpeta;
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}
$actuales = array_values(array_filter(scandir($carpeta), function ($f) use ($carpeta) {
    return $f[0] !== '.' && is_file($carpeta . '/' . $f);
}));
$quedan = array_diff($actuales, $quitar);

$hay_archivos = isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0]);
if (!$hay_archivos && !$quedan && $enlaces === null) {
    denegar(400, 'La tarea debe tener al menos un archivo o un enlace de video');
}

// Actualizar tarea en la base de datos con la ruta de la carpeta; los archivos solo cambian si se guardó
$consulta = $MTA->Modificar_Tarea($id, $asig, $tema, $fecha, $descrip, $carpeta);
if ($consulta == 1) {
    $MTA->Guardar_Enlaces_Video($carpeta, $enlaces);
    foreach (array_intersect($actuales, $quitar) as $f) {
        @unlink($carpeta . '/' . $f);
    }
    if ($hay_archivos) {
        $archivos = $_FILES['archivos'];
        for ($i = 0; $i < count($archivos['name']); $i++) {
            $nombrearchivo = strtoupper(nombre_documento_seguro($archivos['name'][$i]));
            if ($archivos['name'][$i] !== '' && !move_uploaded_file($archivos['tmp_name'][$i], $carpeta . '/' . $nombrearchivo)) {
                denegar(400, 'Error al guardar el archivo: ' . htmlspecialchars($archivos['name'][$i], ENT_QUOTES, 'UTF-8'));
            }
        }
    }
}

echo $consulta;
?>
