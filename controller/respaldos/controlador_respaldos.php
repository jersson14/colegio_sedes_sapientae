<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/respaldo.php';
// Respaldos de la base (solo administrador): listar, generar, descargar y eliminar
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? 'listar');
session_write_close();   // generar o descargar puede tardar: no bloquear las demás pantallas

if ($accion === 'descargar') {
    $ruta = respaldo_ruta($_GET['archivo'] ?? '');
    if (!$ruta) {
        http_response_code(404);
        exit('Respaldo no encontrado');
    }
    set_time_limit(0);
    header('Content-Type: application/octet-stream');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($ruta));
    header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
    readfile($ruta);
    exit;
}

if ($accion === 'generar') {
    try {
        $r = respaldo_generar();
    } catch (PDOException $e) {
        error_log('Respaldo: ' . $e->getMessage());
        denegar(500, 'No se pudo generar el respaldo (error de la base de datos). Revise el registro de errores del servidor.');
    } catch (RuntimeException $e) {
        denegar(409, $e->getMessage());   // otro respaldo en curso
    } catch (Throwable $e) {
        error_log('Respaldo: ' . $e->getMessage());
        denegar(500, 'No se pudo generar el respaldo. Revise el registro de errores del servidor.');
    }
    responder_json(['ok' => true] + $r);
}

if ($accion === 'eliminar') {
    $ruta = respaldo_ruta($_POST['archivo'] ?? '');
    if (!$ruta) {
        denegar(404, 'Respaldo no encontrado');
    }
    if (count(respaldos_listar()) <= 1) {
        denegar(400, 'No se puede eliminar el único respaldo que existe');
    }
    unlink($ruta);
    @file_put_contents(RESPALDOS_DIR . '/respaldos.log', date('Y-m-d H:i:s') . ' eliminado ' . basename($ruta) . ' (usuario ' . usuario_actual_id() . ")\n", FILE_APPEND);
    responder_json(['ok' => true]);
}

responder_json(['respaldos' => respaldos_listar()]);
