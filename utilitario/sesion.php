<?php
/**
 * Manejo común de sesión, roles y token CSRF.
 * Lo usan las páginas (index.php, view/index.php), los controladores (controller/_seguridad.php)
 * y los reportes MPDF.
 */

function iniciar_sesion_segura() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    // Zona horaria del país configurado desde el inicio de la petición: así date() en PHP coincide con
    // CURDATE()/NOW() de MySQL (el php.ini del servidor puede tener otra, p. ej. Europe/Berlin)
    require_once __DIR__ . '/regional.php';
    date_default_timezone_set(config_regional()['zona_horaria']);
}

function hay_sesion() {
    return isset($_SESSION['S_ID'], $_SESSION['S_ROL']);
}

function rol_actual() {
    return $_SESSION['S_ROL'] ?? '';
}

function usuario_actual_id() {
    return $_SESSION['S_ID'] ?? null;
}

function csrf_token() {
    if (empty($_SESSION['CSRF_TOKEN'])) {
        $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['CSRF_TOKEN'];
}

function csrf_valido() {
    $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['CSRF_TOKEN']) && is_string($enviado)
        && hash_equals($_SESSION['CSRF_TOKEN'], $enviado);
}

/** Corta la petición con un código HTTP y un mensaje JSON. */
function denegar($codigo, $mensaje) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'mensaje' => $mensaje]);
    exit;
}

/** Deja constancia de accesos rechazados para detectar permisos mal configurados. */
function registrar_acceso_denegado($ruta, $motivo) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $linea = sprintf("%s\t%s\tusuario=%s\trol=%s\tip=%s\t%s\n",
        date('Y-m-d H:i:s'), $motivo, usuario_actual_id() ?? '-', rol_actual() ?: '-',
        $_SERVER['REMOTE_ADDR'] ?? '-', $ruta);
    @file_put_contents($dir . '/accesos_denegados.log', $linea, FILE_APPEND | LOCK_EX);
}
