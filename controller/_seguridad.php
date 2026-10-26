<?php
/**
 * Control de acceso de los controladores. Cada controlador lo incluye en su primera línea.
 *
 * - Sin sesión: 401.
 * - Rol no permitido para ese controlador (según _permisos.php): 403.
 * - POST sin token CSRF válido: 403.
 * - Parámetros "propios": para roles distintos de ADMINISTRADOR se reemplazan por el id
 *   de la sesión, así un estudiante o docente solo consulta sus propios datos.
 * - Parámetros "propio_sesion": igual, pero tomando otro dato de la sesión (DNI, foto actual).
 * - Parámetros "usuario_sesion": siempre se reemplazan por el id de la sesión
 *   (quién registra un movimiento no lo decide el navegador).
 */
require_once __DIR__ . '/../utilitario/sesion.php';

iniciar_sesion_segura();

// Errores no previstos en los controladores (Fase 13): en vez de un error de PHP, una respuesta clara.
// - Datos relacionados (clave foránea, 23000), p. ej. eliminar un aula con matrículas: 409 con el motivo.
// - Cualquier otro: 500 genérico; el detalle queda solo en el registro de errores del servidor.
set_exception_handler(function ($e) {
    error_log('Controlador ' . ($_SERVER['SCRIPT_NAME'] ?? '') . ': ' . $e->getMessage());
    if (headers_sent()) {
        return;
    }
    if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate entry') !== false) {
            denegar(409, 'Ese registro ya existe. Revise los datos e intente de nuevo.');
        }
        if (stripos($msg, 'foreign key') !== false && stripos($msg, 'delete') !== false) {
            denegar(409, 'No se puede eliminar porque tiene información relacionada (matrículas, notas, pagos u otros registros). '
                . 'Si ya no se usa, desactívelo en lugar de eliminarlo.');
        }
        denegar(409, 'No se puede guardar porque falta o no coincide un dato relacionado. Revise las opciones elegidas.');
    }
    denegar(500, 'Ocurrió un error inesperado. Intente de nuevo; si continúa, avise al administrador.');
});

// Contraseña temporal pendiente de cambio: solo se permite cambiarla o cerrar la sesión
if (!empty($_SESSION['S_DEBE_CAMBIAR'])
    && !in_array(basename($_SERVER['SCRIPT_FILENAME']), ['controlador_cambiar_mi_clave.php', 'controlador_cerrar_sesion.php'], true)) {
    denegar(403, 'Debe cambiar su contraseña temporal antes de continuar');
}

(function () {
    $permisos = require __DIR__ . '/_permisos.php';
    $raiz = str_replace('\\', '/', realpath(__DIR__ . '/..')) . '/';
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
    $ruta = (strpos($script, $raiz) === 0) ? substr($script, strlen($raiz)) : $script;

    if (in_array($ruta, $permisos['publico'], true)) {
        return;
    }

    if (!hay_sesion()) {
        registrar_acceso_denegado($ruta, 'SIN_SESION');
        denegar(401, 'Sesión no iniciada o expirada');
    }

    $rol = rol_actual();
    $permitidos = $permisos['roles'][$ruta] ?? ['ADMINISTRADOR'];
    if ($rol !== 'ADMINISTRADOR' && !in_array($rol, $permitidos, true)) {
        registrar_acceso_denegado($ruta, 'ROL_NO_PERMITIDO');
        denegar(403, 'No tiene permiso para esta acción');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
        registrar_acceso_denegado($ruta, 'CSRF_INVALIDO');
        denegar(403, 'Token de seguridad inválido. Recargue la página.');
    }

    if ($rol !== 'ADMINISTRADOR') {
        foreach ($permisos['propio'][$ruta] ?? [] as $campo) {
            $_POST[$campo] = (string) usuario_actual_id();
        }
        foreach ($permisos['propio_sesion'][$ruta] ?? [] as $campo => $clave) {
            $_POST[$campo] = html_entity_decode((string) ($_SESSION[$clave] ?? ''), ENT_QUOTES, 'UTF-8');
        }
    }
    foreach ($permisos['usuario_sesion'][$ruta] ?? [] as $campo) {
        $_POST[$campo] = (string) usuario_actual_id();
    }
})();
