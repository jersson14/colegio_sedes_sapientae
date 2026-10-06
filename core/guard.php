<?php
declare(strict_types=1);

/**
 * Guard de endpoints (Fase 0, punto 0.2).
 *
 * Se incluye en la PRIMERA línea de todo controlador no público:
 *     require_once __DIR__ . '/../../core/guard.php';
 *
 * - Sin sesión válida (o expirada) responde 401 y corta la ejecución.
 * - Peticiones no-GET sin token CSRF válido responden 419.
 * - Expone exigir_rol() para que cada controlador declare su autorización.
 *
 * Endpoints públicos (NO llevan guard): usuario/controlador_iniciar_sesion.php,
 * usuario/controlador_cerrar_sesion.php y controlador_solicitudes.php (landing).
 */

require_once __DIR__ . '/sesion.php';

function responder_error(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => $mensaje]));
}

function exigir_rol(string ...$roles): void
{
    if (!in_array($_SESSION['S_ROL'] ?? '', $roles, true)) {
        responder_error(403, 'Sin permiso para esta acción');
    }
}

function verificar_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return;
    }
    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)$recibido)) {
        responder_error(419, 'Token CSRF inválido');
    }
}

if (!sesion_activa()) {
    responder_error(401, 'No autenticado');
}

// Ningún controlador escribe en la sesión: se libera el bloqueo del archivo
// para que las peticiones AJAX en paralelo no se serialicen. $_SESSION sigue
// disponible para lectura.
session_write_close();

// CSRF (Fase 0.5): toda petición que no sea GET debe traer el token de la sesión.
// El panel lo envía en la cabecera X-CSRF-Token (ver $.ajaxPrefilter en view/index.php).
verificar_csrf();
