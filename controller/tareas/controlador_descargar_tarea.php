<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE', 'AUXILIAR');
    require_once __DIR__ . '/../../core/subidas.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
/**
 * Descarga autenticada de archivos de tareas (Fase 0.3-B).
 * Sustituye al listado público de directorio: la carpeta de subidas ya no es
 * accesible por URL directa (.htaccess con "Require all denied").
 *
 * GET ?carpeta=<ruta guardada en la BD>              → lista de archivos
 * GET ?carpeta=<ruta guardada en la BD>&archivo=X    → envía el archivo
 *
 * Pertenencia: el estudiante solo ve tareas asignadas y sus envíos (core/pertenencia.php).
 * Pendiente: restringir al DOCENTE a sus propias aulas.
 */

// Ruta física: el almacén de la institución (Fase 4.6) o, en modo único, la carpeta anterior.
$nombreCarpeta = carpeta_tarea_valida((string)($_GET['carpeta'] ?? ''));
$dir = $nombreCarpeta !== null ? tarea_carpeta_fisica($nombreCarpeta) : null;
if ($dir === null) {
    responder_error(404, 'Tarea sin archivos');
}
// IDOR: el estudiante solo abre tareas asignadas a él o sus propios envíos.
exigir_carpeta_tarea_visible($nombreCarpeta);

$archivos = array_values(array_filter(
    scandir($dir),
    fn($n) => $n[0] !== '.' && is_file($dir . DIRECTORY_SEPARATOR . $n)
        && in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), DOCUMENTO_EXTENSIONES, true)
));

// --- Enviar un archivo concreto
if (isset($_GET['archivo'])) {
    $pedido = (string)$_GET['archivo'];
    if (!in_array($pedido, $archivos, true)) {   // solo nombres que existen en la carpeta
        responder_error(404, 'Archivo no encontrado');
    }
    $ruta = $dir . DIRECTORY_SEPARATOR . $pedido;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta) ?: 'application/octet-stream';
    $enLinea = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain'], true);
    if (!$enLinea) {
        $mime = 'application/octet-stream';
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($ruta));
    header('Content-Disposition: ' . ($enLinea ? 'inline' : 'attachment')
        . '; filename="' . preg_replace('/[^\x20-\x7E]|"/', '_', $pedido) . '"'
        . "; filename*=UTF-8''" . rawurlencode($pedido));
    readfile($ruta);
    exit;
}

// --- Lista de archivos de la tarea
$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Archivos de la tarea</title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 40rem; padding: 0 1rem; color: #212529; }
    h1 { font-size: 1.25rem; }
    ul { list-style: none; padding: 0; }
    li { padding: .5rem 0; border-bottom: 1px solid #dee2e6; }
    a { color: #0d6efd; text-decoration: none; word-break: break-all; }
    a:hover { text-decoration: underline; }
    .vacio { color: #6c757d; }
  </style>
</head>
<body>
  <h1>Archivos de la tarea</h1>
  <?php if (!$archivos) { ?>
    <p class="vacio">Esta tarea no tiene archivos adjuntos.</p>
  <?php } else { ?>
    <ul>
      <?php foreach ($archivos as $a) { ?>
        <li><a href="?carpeta=<?= $e(rawurlencode($nombreCarpeta)) ?>&amp;archivo=<?= $e(rawurlencode($a)) ?>"><?= $e($a) ?></a></li>
      <?php } ?>
    </ul>
  <?php } ?>
</body>
</html>
