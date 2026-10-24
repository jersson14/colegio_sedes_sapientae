<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_conexion.php';
    // Muestra el comprobante adjunto de un egreso (la carpeta no es accesible directamente)
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("SELECT adjunto FROM egresos WHERE id_egresos = ?");
    $q->execute([$id]);
    $ruta = $q->fetchColumn();
    $base = realpath(__DIR__ . '/comprobantes');
    $archivo = $ruta ? realpath(__DIR__ . '/../../' . $ruta) : false;
    if (!$base || !$archivo || strpos($archivo, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivo)) {
        http_response_code(404);
        exit('El egreso no tiene comprobante adjunto');
    }
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($archivo) ?: 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . basename($archivo) . '"');
    readfile($archivo);
?>
