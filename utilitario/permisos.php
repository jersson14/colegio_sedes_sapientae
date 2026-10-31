<?php
/**
 * Permisos por rol (Fase 13, paso 15): los de fábrica (controller/_permisos.php) más los cambios que la dirección hace
 * por pantalla en Configuración → Permisos por rol (tabla permisos_pantalla).
 * - Dar una pantalla a un rol habilita todos los controladores que esa pantalla usa (controller/_pantallas.php).
 * - Quitarla bloquea sus controladores, salvo los que el rol sigue necesitando en otra pantalla suya o en su inicio.
 * La usan controller/_seguridad.php (controladores) y utilitario/seguridad_reporte.php (reportes PDF).
 */
require_once __DIR__ . '/../model/model_conexion.php';

/** Pantallas que solo la dirección puede tener: darlas a otro rol le permitiría cambiar sus propios permisos o el sistema. */
const PERMISOS_SOLO_DIRECCION = ['configuracion/view_permisos.php', 'configuracion/view_parametros.php', 'configuracion/view_respaldos.php'];

/** Cambios de la dirección para un rol: [vista => 1 (dada) | 0 (quitada)]. Vacío si aún no hay cambios o falta la tabla. */
function permisos_cambios($rol) {
    static $cache = [];
    if (isset($cache[$rol])) { return $cache[$rol]; }
    $cache[$rol] = [];
    try {
        $q = (new conexionBD())->conexionPDO()->prepare("SELECT vista, permitido FROM permisos_pantalla WHERE rol = ?");
        $q->execute([$rol]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $cache[$rol][$r['vista']] = (int) $r['permitido']; }
    } catch (Throwable $e) {
        // sin la tabla (migración 022 pendiente) rigen solo los permisos de fábrica
    }
    return $cache[$rol];
}

function permisos_mapa() {
    static $mapa = null;
    if ($mapa === null) {
        $f = __DIR__ . '/../controller/_pantallas.php';
        $mapa = is_file($f) ? require $f : ['pantallas' => [], 'roles' => []];
    }
    return $mapa;
}

/** ¿Puede el rol usar ese archivo (ruta desde la raíz, p. ej. controller/pensiones/controlador_listar_pensiones.php)? */
function permiso_rol_ruta($rol, $ruta, array $permisos) {
    if ($rol === 'ADMINISTRADOR') { return true; }
    $fabrica = in_array($rol, $permisos['roles'][$ruta] ?? [], true);
    $cambios = permisos_cambios($rol);
    if (!$cambios) { return $fabrica; }
    $mapa = permisos_mapa();
    $usa = function ($vista) use ($mapa, $ruta) { return in_array($ruta, $mapa['pantallas'][$vista]['controladores'] ?? [], true); };
    foreach ($cambios as $vista => $permitido) {
        if ($permitido === 1 && $usa($vista) && !in_array($vista, PERMISOS_SOLO_DIRECCION, true)) { return true; }
    }
    if (!$fabrica) { return false; }
    $en_quitada = false;
    foreach ($cambios as $vista => $permitido) {
        if ($permitido === 0 && $usa($vista)) { $en_quitada = true; break; }
    }
    if (!$en_quitada) { return true; }
    // Lo usa una pantalla quitada: sigue permitido si lo necesita su inicio u otra pantalla suya que conserva
    if (in_array($ruta, $mapa['roles'][$rol]['indice'] ?? [], true)) { return true; }
    foreach ($mapa['roles'][$rol]['vistas'] ?? [] as $vista) {
        if (($cambios[$vista] ?? 1) !== 0 && $usa($vista)) { return true; }
    }
    return false;
}

/** Pantallas que el rol tiene hoy: [vista => ['titulo', 'seccion', 'origen' => 'FABRICA'|'DADA']] (sin las quitadas). */
function permisos_pantallas_rol($rol) {
    $mapa = permisos_mapa();
    $cambios = permisos_cambios($rol);
    $res = [];
    foreach ($mapa['roles'][$rol]['vistas'] ?? [] as $v) {
        if (($cambios[$v] ?? 1) !== 0 && isset($mapa['pantallas'][$v])) { $res[$v] = $mapa['pantallas'][$v] + ['origen' => 'FABRICA']; }
    }
    foreach ($cambios as $v => $p) {
        if ($p === 1 && !isset($res[$v]) && isset($mapa['pantallas'][$v]) && !in_array($v, PERMISOS_SOLO_DIRECCION, true)) { $res[$v] = $mapa['pantallas'][$v] + ['origen' => 'DADA']; }
    }
    return $res;
}
