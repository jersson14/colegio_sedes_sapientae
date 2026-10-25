<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require_once '../../model/model_tareas.php';
/**
 * Lista y entrega los archivos de una tarea (la que publica el docente o la que envía el estudiante).
 * Reemplaza el listado directo de la carpeta "documentos", que era público.
 *   ?carpeta=controller/tareas/documentos/tarea_alumnos_123               -> página con videos, enlaces y archivos
 *   ?carpeta=controller/tareas/documentos/tarea_alumnos_123&archivo=X     -> abre/descarga X (los videos se transmiten)
 *   ?carpeta=controller/tareas/documentos/tarea_alumnos_123&formato=json  -> lista para el formulario de edición
 */
$ruta_bd = $_GET['carpeta'] ?? '';
$nombre_carpeta = carpeta_tarea_valida($ruta_bd);
// Los archivos se guardan en controller/tareas/controller/tareas/documentos (ruta relativa histórica)
$dir = realpath(__DIR__ . '/controller/tareas/documentos/' . $nombre_carpeta);
if ($nombre_carpeta === '' || !$dir || !is_dir($dir)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

$MTA = new Modelo_Tareas();
$c = $MTA->conexionPDO();
$ruta = 'controller/tareas/documentos/' . $nombre_carpeta;
if (rol_actual() === 'ESTUDIANTE') {
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
} elseif (rol_actual() === 'DOCENTE') {
    // El docente ve el material y los envíos de las tareas de sus cursos
    $q = $c->prepare("SELECT t.id_tarea FROM tareas t LEFT JOIN detalle_tarea d ON d.id_tarea = t.id_tarea
                      WHERE t.archivo_tarea = ? OR d.archivo_evnio_tarea = ? LIMIT 1");
    $q->execute([$ruta, $ruta]);
    $idtarea = $q->fetchColumn();
    if ($idtarea === false || !$MTA->Tarea_Es_Del_Docente($idtarea, usuario_actual_id())) {
        registrar_acceso_denegado('controller/tareas/controlador_archivos_tarea.php', 'TAREA_AJENA');
        http_response_code(403);
        exit('No tiene permiso para ver estos archivos');
    }
}
// Los videos pueden tardar: liberar la sesión para no bloquear las demás pantallas del usuario
session_write_close();

$archivos = array_values(array_filter(scandir($dir), function ($f) use ($dir) {
    return $f[0] !== '.' && is_file($dir . DIRECTORY_SEPARATOR . $f);
}));
$enlaces = enlaces_video_lista($MTA->Enlaces_Video($ruta));

if (isset($_GET['archivo'])) {
    $nombre = basename((string) $_GET['archivo']);
    if (!in_array($nombre, $archivos, true)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }
    $path = $dir . DIRECTORY_SEPARATOR . $nombre;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    $tam = filesize($path);
    // PDF, imágenes y videos se abren en el navegador; lo demás se descarga
    $video = strpos($mime, 'video/') === 0;
    $en_linea = $video || in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    header('Content-Type: ' . ($mime === 'video/quicktime' ? 'video/mp4' : $mime));
    header('X-Content-Type-Options: nosniff');
    header('Accept-Ranges: bytes');
    header('Content-Disposition: ' . ($en_linea ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($nombre));
    // Por partes (Range): el reproductor pide solo lo que va a mostrar y se puede adelantar el video
    $inicio = 0;
    $fin = $tam - 1;
    if (preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'] ?? '', $m) && ($m[1] !== '' || $m[2] !== '')) {
        if ($m[1] === '') {
            $inicio = max(0, $tam - (int) $m[2]);
        } else {
            $inicio = (int) $m[1];
            $fin = $m[2] === '' ? $tam - 1 : min((int) $m[2], $tam - 1);
        }
        if ($inicio > $fin || $inicio >= $tam) {
            http_response_code(416);
            header("Content-Range: bytes */$tam");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes $inicio-$fin/$tam");
    }
    header('Content-Length: ' . ($fin - $inicio + 1));
    set_time_limit(0);
    $fp = fopen($path, 'rb');
    fseek($fp, $inicio);
    $restante = $fin - $inicio + 1;
    while ($restante > 0 && !feof($fp) && !connection_aborted()) {
        $bloque = fread($fp, min(1048576, $restante));
        echo $bloque;
        flush();
        $restante -= strlen($bloque);
    }
    fclose($fp);
    exit;
}

if (($_GET['formato'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'archivos' => array_map(function ($f) use ($dir) {
            return ['nombre' => $f, 'video' => es_video($f), 'tamano' => filesize($dir . DIRECTORY_SEPARATOR . $f)];
        }, $archivos),
        'enlaces' => array_column($enlaces, 'url'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$e = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$url_archivo = function ($f) use ($ruta_bd) { return '?carpeta=' . urlencode($ruta_bd) . '&archivo=' . urlencode($f); };
$videos = array_values(array_filter($archivos, 'es_video'));
$documentos = array_values(array_diff($archivos, $videos));
$mb = function ($f) use ($dir) {
    $b = filesize($dir . DIRECTORY_SEPARATOR . $f);
    return $b < 1048576 ? max(1, round($b / 1024)) . ' KB' : number_format($b / 1048576, 1) . ' MB';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Material de la tarea</title>
  <link rel="stylesheet" href="../../plantilla/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../../plantilla/plugins/fontawesome-free/css/all.min.css">
  <style>
    .video-caja { position: relative; width: 100%; padding-top: 56.25%; background: #000; border-radius: 4px; overflow: hidden; }
    .video-caja iframe, .video-caja video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
  </style>
</head>
<body class="p-3">
  <?php if ($videos || $enlaces) { ?>
  <div class="card card-danger card-outline">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-video"></i> Videos</h3></div>
    <div class="card-body">
      <div class="row">
        <?php foreach ($videos as $f) { ?>
          <div class="col-lg-6 mb-4">
            <div class="video-caja">
              <video controls preload="metadata" src="<?php echo $e($url_archivo($f)); ?>"></video>
            </div>
            <div class="mt-1"><i class="fas fa-film"></i> <?php echo $e($f); ?> <small class="text-muted">(<?php echo $mb($f); ?>)</small></div>
          </div>
        <?php } ?>
        <?php foreach ($enlaces as $enlace) { $src = video_incrustado($enlace['url']); ?>
          <div class="col-lg-6 mb-4">
            <?php if ($src !== '') { ?>
              <div class="video-caja">
                <iframe src="<?php echo $e($src); ?>" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                        allowfullscreen referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe>
              </div>
            <?php } ?>
            <div class="mt-1 text-truncate">
              <a href="<?php echo $e($enlace['url']); ?>" target="_blank" rel="noopener noreferrer" class="<?php echo $src === '' ? 'btn btn-outline-danger btn-sm' : ''; ?>">
                <i class="fas fa-external-link-alt"></i> <?php echo $src === '' ? 'Abrir video: ' : ''; ?><?php echo $e($enlace['url']); ?>
              </a>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <?php } ?>
  <div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-folder-open"></i> Archivos de la tarea</h3></div>
    <div class="card-body">
      <?php if (!$documentos) { ?>
        <p class="text-muted mb-0"><?php echo ($videos || $enlaces) ? 'Esta tarea solo tiene videos.' : 'La carpeta no tiene archivos.'; ?></p>
      <?php } else { ?>
        <ul class="list-group">
          <?php foreach ($documentos as $f) { ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><i class="fas fa-file"></i> <?php echo $e($f); ?></span>
              <a class="btn btn-success btn-sm" target="_blank" href="<?php echo $e($url_archivo($f)); ?>">
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
