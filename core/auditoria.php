<?php

declare(strict_types=1);

/**
 * Auditoría de la institución (H-15). core/guard.php registra al terminar toda petición que no es GET
 * (con su código de respuesta: también los 403, 419 y 402); el inicio y el cierre de sesión se registran
 * aparte. Un fallo al auditar nunca rompe la respuesta: va al log.
 */

require_once __DIR__ . '/tenant.php';

use App\Auditoria\RegistroAuditoria;
use App\Core\Conexion;

function auditoria_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function auditoria_registrar(?int $usuId, string $usuario, string $rol, string $modulo, string $accion, int $estado, ?string $datos): void
{
    try {
        (new RegistroAuditoria(Conexion::crear()))->registrar($usuId, $usuario, $rol, $modulo, $accion, $estado, auditoria_ip(), $datos);
    } catch (\Throwable $e) {
        error_log('Auditoría no registrada: ' . $e->getMessage());
    }
}

/** Lo que se registra al terminar una petición de escritura del usuario en sesión. */
function auditoria_registrar_peticion(): void
{
    [$modulo, $accion] = RegistroAuditoria::moduloYAccion((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $estado = http_response_code();
    $estado = is_int($estado) ? $estado : 200;
    if (RegistroAuditoria::esLecturaRutinaria($accion, $estado)) {
        return;
    }
    auditoria_registrar(
        isset($_SESSION['S_ID']) ? (int) $_SESSION['S_ID'] : null,
        (string) ($_SESSION['S_USU'] ?? ''),
        (string) ($_SESSION['S_ROL'] ?? ''),
        $modulo,
        $accion,
        $estado,
        RegistroAuditoria::datosSeguros($_POST, $_FILES)
    );
}
