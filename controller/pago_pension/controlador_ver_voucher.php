<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_conexion.php';
    // Muestra el voucher de un recibo (la carpeta de vouchers no es accesible directamente)
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("SELECT voucher FROM pagos WHERE id_pago = ?");
    $q->execute([$id]);
    $ruta = $q->fetchColumn();
    $base = realpath(__DIR__ . '/vouchers');
    $archivo = $ruta ? realpath(__DIR__ . '/../../' . $ruta) : false;
    if (!$base || !$archivo || strpos($archivo, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivo)) {
        http_response_code(404);
        exit('El recibo no tiene voucher');
    }
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($archivo) ?: 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . basename($archivo) . '"');
    readfile($archivo);
?>
