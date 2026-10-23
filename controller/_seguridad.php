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
