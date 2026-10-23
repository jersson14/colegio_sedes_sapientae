<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require_once '../../model/model_conexion.php';
/**
 * Lista y entrega los archivos de una tarea (la que publica el docente o la que envía el estudiante).
 * Reemplaza el listado directo de la carpeta "documentos", que era público.
 *   ?carpeta=controller/tareas/documentos/tarea_alumnos_123            -> lista de archivos
 *   ?carpeta=controller/tareas/documentos/tarea_alumnos_123&archivo=X  -> descarga X
 */
$ruta_bd = $_GET['carpeta'] ?? '';
$nombre_carpeta = carpeta_tarea_valida($ruta_bd);
// Los archivos se guardan en controller/tareas/controller/tareas/documentos (ruta relativa histórica)
$dir = realpath(__DIR__ . '/controller/tareas/documentos/' . $nombre_carpeta);
if ($nombre_carpeta === '' || !$dir || !is_dir($dir)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

if (rol_actual() === 'ESTUDIANTE') {
    $c = (new conexionBD())->conexionPDO();
    $ruta = 'controller/tareas/documentos/' . $nombre_carpeta;
    $q = $c->prepare("SELECT 1 FROM detalle_tarea d
                      INNER JOIN matricula m ON d.id_matriculado = m.id_matricula
                      INNER JOIN tareas t ON t.id_tarea = d.id_tarea
                      WHERE m.usu_id = ? AND (t.archivo_tarea = ? OR d.archivo_evnio_tarea = ?) LIMIT 1");
    $q->execute([usuario_actual_id(), $ruta, $ruta]);
    if (!$q->fetchColumn()) {
        registrar_acceso_denegado('controller/tareas/controlador_archivos_tarea.php', 'TAREA_AJENA');
        http_response_code(403);
        exit('No tiene permiso para ver estos archivos');
    }
}

$archivos = array_values(array_filter(scandir($dir), function ($f) use ($dir) {
    return $f[0] !== '.' && is_file($dir . DIRECTORY_SEPARATOR . $f);
}));

if (isset($_GET['archivo'])) {
    $nombre = basename((string) $_GET['archivo']);
    if (!in_array($nombre, $archivos, true)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }
    $path = $dir . DIRECTORY_SEPARATOR . $nombre;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    // Solo PDF e imágenes se abren en el navegador; lo demás se descarga
    $en_linea = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($en_linea ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($nombre));
    readfile($path);
    exit;
}

$e = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Archivos de la tarea</title>
  <link rel="stylesheet" href="../../plantilla/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../../plantilla/plugins/fontawesome-free/css/all.min.css">
</head>
<body class="p-3">
  <div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-folder-open"></i> Archivos de la tarea</h3></div>
    <div class="card-body">
      <?php if (!$archivos) { ?>
        <p class="text-muted">La carpeta no tiene archivos.</p>
      <?php } else { ?>
        <ul class="list-group">
          <?php foreach ($archivos as $f) { ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><i class="fas fa-file"></i> <?php echo $e($f); ?></span>
              <a class="btn btn-success btn-sm" target="_blank"
                 href="?carpeta=<?php echo urlencode($ruta_bd); ?>&archivo=<?php echo urlencode($f); ?>">
                <i class="fas fa-file-download"></i> Abrir
              </a>
            </li>
          <?php } ?>
        </ul>
      <?php } ?>
    </div>
  </div>
</body>
</html>
