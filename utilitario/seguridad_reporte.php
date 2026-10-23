<?php
/**
 * Control de acceso de los reportes MPDF (view/MPDF/REPORTE/*.php).
 * Usa los mismos permisos por rol que los controladores (controller/_permisos.php).
 */
require_once __DIR__ . '/sesion.php';
iniciar_sesion_segura();

(function () {
    $ruta = 'view/MPDF/REPORTE/' . basename($_SERVER['SCRIPT_FILENAME']);
    if (!hay_sesion()) {
        registrar_acceso_denegado($ruta, 'SIN_SESION');
        http_response_code(401);
        exit('Sesión no iniciada o expirada');
    }
    $permisos = require __DIR__ . '/../controller/_permisos.php';
    $permitidos = $permisos['roles'][$ruta] ?? ['ADMINISTRADOR'];
    if (rol_actual() !== 'ADMINISTRADOR' && !in_array(rol_actual(), $permitidos, true)) {
        registrar_acceso_denegado($ruta, 'ROL_NO_PERMITIDO');
        http_response_code(403);
        exit('No tiene permiso para ver este reporte');
    }
})();

/** Un estudiante solo puede ver reportes de sus propias matrículas. */
function reporte_exigir_matricula_propia($mysqli, $id_matricula) {
    if (rol_actual() !== 'ESTUDIANTE') {
        return;
    }
    $st = $mysqli->prepare('SELECT 1 FROM matricula WHERE id_matricula = ? AND usu_id = ?');
    $uid = (int) usuario_actual_id();
    $id = (int) $id_matricula;
    $st->bind_param('ii', $id, $uid);
    $st->execute();
    if (!$st->get_result()->fetch_row()) {
        registrar_acceso_denegado(basename($_SERVER['SCRIPT_FILENAME']), 'MATRICULA_AJENA');
        http_response_code(403);
        exit('No tiene permiso para ver este reporte');
    }
}

/** Un estudiante solo puede ver el horario de un aula en la que está matriculado. */
function reporte_exigir_aula_propia($mysqli, $id_aula) {
    if (rol_actual() !== 'ESTUDIANTE') {
        return;
    }
    $st = $mysqli->prepare('SELECT 1 FROM matricula WHERE id_aula = ? AND usu_id = ?');
    $uid = (int) usuario_actual_id();
    $id = (int) $id_aula;
    $st->bind_param('ii', $id, $uid);
    $st->execute();
    if (!$st->get_result()->fetch_row()) {
        registrar_acceso_denegado(basename($_SERVER['SCRIPT_FILENAME']), 'AULA_AJENA');
        http_response_code(403);
        exit('No tiene permiso para ver este reporte');
    }
}
