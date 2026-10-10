<?php

declare(strict_types=1);

/**
 * Manejo centralizado de la sesión (Fase 0, puntos 0.1 y 0.8).
 *
 * La sesión se construye SOLO en el servidor, a partir de los datos que
 * devuelve la BD tras verificar la contraseña. Ningún dato de sesión
 * (y en especial el rol) se acepta desde el cliente.
 */

require_once __DIR__ . '/tenant.php';

const SESION_INACTIVIDAD_MAX = 1800; // 30 minutos

function sesion_iniciar(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    tenant_actual(); // un host que no es de ninguna institución no llega a abrir sesión
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Crea la sesión de un usuario ya autenticado.
 * $fila es el registro devuelto por SP_VERIFICAR_USUARIO.
 */
function sesion_crear(array $fila): void
{
    sesion_iniciar();
    session_regenerate_id(true); // evita fijación de sesión

    $texto = static fn ($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

    $_SESSION = [];
    $_SESSION['S_ID']              = $texto($fila['usu_id']);
    $_SESSION['S_USU']             = $texto($fila['usu_usuario']);
    $_SESSION['S_NOMBRE']          = $texto($fila['docente_nombre']);
    $_SESSION['S_COMPLETO']        = $texto($fila['Docente']);
    $_SESSION['S_ROL']             = $texto($fila['tipo_rol']);
    $_SESSION['S_FOTO']            = $texto($fila['docente_fotoperfil']);
    $_SESSION['S_MOVIL']           = $texto($fila['docente_movil']);
    $_SESSION['S_DIRECCION']       = $texto($fila['docente_direccion']);
    $_SESSION['S_FECHANACIMIENTO'] = $texto($fila['fechana']);
    $_SESSION['S_EMAIL']           = $texto($fila['usu_email']);
    $_SESSION['S_DNI']             = $texto($fila['docente_dni']);
    // Fase 4: la sesión pertenece a una institución. Los archivos de sesión son comunes a todo el
    // servidor; sin esta marca, una cookie copiada de un colegio abriría la sesión en otro.
    $_SESSION['S_TENANT']          = tenant_actual()->slug;

    $_SESSION['csrf_token']       = bin2hex(random_bytes(32));
    $_SESSION['ultima_actividad'] = time();
}

/** Destruye la sesión y borra la cookie del navegador. */
function sesion_destruir(): void
{
    sesion_iniciar();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'],
    ]);
    session_destroy();
}

/**
 * Devuelve true si hay un usuario autenticado y su sesión no expiró.
 * Renueva la marca de actividad en cada llamada.
 */
function sesion_activa(): bool
{
    sesion_iniciar();
    if (!isset($_SESSION['S_ID'])) {
        return false;
    }
    // Sesión de otra institución (cookie copiada a otro subdominio): no vale aquí. No se destruye:
    // pertenece a su colegio, y quien la presenta en otro no debe poder cerrarla.
    if (($_SESSION['S_TENANT'] ?? null) !== tenant_actual()->slug) {
        return false;
    }
    if (isset($_SESSION['ultima_actividad'])
        && (time() - $_SESSION['ultima_actividad']) > SESION_INACTIVIDAD_MAX) {
        sesion_destruir();
        return false;
    }
    $_SESSION['ultima_actividad'] = time();
    // Sesiones creadas antes de existir el token CSRF.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return true;
}
