<?php
/**
 * Parámetros del sistema que la dirección cambia en Configuración → Parámetros (Fase 13, paso 15).
 * Orden: lo guardado en la tabla parametros; si no hay, config/config.php; si tampoco, el valor de fábrica.
 */
require_once __DIR__ . '/../model/model_conexion.php';

/** Valores de fábrica y su tipo: clave => [valor, tipo, mínimo, máximo]. */
function parametros_definicion() {
    $cfg = require __DIR__ . '/../config/config.php';
    return [
        'login_max_intentos'    => [(string) ($cfg['login_max_intentos'] ?? 5), 'entero', 0, 50],     // 0 = sin bloqueo
        'login_minutos_bloqueo' => [(string) ($cfg['login_minutos_bloqueo'] ?? 15), 'entero', 1, 1440],
        'video_max_mb'          => [(string) ($cfg['video_max_mb'] ?? 100), 'entero', 1, 2048],
        'clases_sabado'         => ['0', 'booleano', 0, 1],
        'asistencia_P'          => ['Presente', 'texto', 1, 20],
        'asistencia_T'          => ['Tardanza', 'texto', 1, 20],
        'asistencia_F'          => ['Falta', 'texto', 1, 20],
        'asistencia_J'          => ['Justificada', 'texto', 1, 20],
    ];
}

function parametros_todos($refrescar = false) {
    static $cache = null;
    if ($cache !== null && !$refrescar) { return $cache; }
    $cache = [];
    foreach (parametros_definicion() as $k => $d) { $cache[$k] = $d[0]; }
    try {
        foreach ((new conexionBD())->conexionPDO()->query("SELECT clave, valor FROM parametros")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (array_key_exists($r['clave'], $cache)) { $cache[$r['clave']] = $r['valor']; }
        }
    } catch (Throwable $e) {
        // sin la tabla (migración 022 pendiente) rigen config.php y los valores de fábrica
    }
    return $cache;
}

function parametro($clave) {
    return parametros_todos()[$clave] ?? null;
}

/** Nombres de las marcas de asistencia: ['P' => 'Presente', ...]. */
function parametros_asistencia() {
    $p = parametros_todos();
    return ['P' => $p['asistencia_P'], 'T' => $p['asistencia_T'], 'F' => $p['asistencia_F'], 'J' => $p['asistencia_J']];
}

/** Límite real de subida del servidor en MB (el menor entre upload_max_filesize y post_max_size). */
function parametros_limite_servidor_mb() {
    $mb = function ($v) {
        $v = trim((string) $v); $n = (float) $v; $u = strtolower(substr($v, -1));
        return $u === 'g' ? $n * 1024 : ($u === 'k' ? $n / 1024 : ($u === 'm' ? $n : $n / 1048576));
    };
    return (int) floor(min($mb(ini_get('upload_max_filesize')), $mb(ini_get('post_max_size'))));
}
