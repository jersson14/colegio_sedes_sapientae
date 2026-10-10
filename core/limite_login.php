<?php

declare(strict_types=1);

/**
 * Límite de intentos de login (hallazgo H-07 de docs/SEGURIDAD.md).
 *
 * Dos contadores en ventana de 15 minutos:
 *   - usuario + IP: 5 fallos → bloqueo, que se duplica en cada reincidencia (máx. 24 h)
 *   - solo IP:     30 fallos → bloqueo (frena el barrido de muchos usuarios)
 * Un login correcto reinicia el contador usuario + IP.
 *
 * Estado en archivos FUERA del docroot (sin tocar el esquema de la BD):
 *   LOGIN_LIMITE_DIR del colegio.env, o <carpeta de colegio.env>/intentos_login/
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tenant.php';

const LIMITE_VENTANA        = 900;    // 15 min
const LIMITE_FALLOS_USUARIO = 5;
const LIMITE_FALLOS_IP      = 30;
const LIMITE_BLOQUEO_BASE   = 900;    // 15 min, se duplica por reincidencia
const LIMITE_BLOQUEO_MAX    = 86400;  // 24 h

function limite_dir(): string
{
    $dir = config('LOGIN_LIMITE_DIR') ?: dirname(config_ruta_env()) . DIRECTORY_SEPARATOR . 'intentos_login';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

/** Usuario + institución + IP: «ana» de un colegio no bloquea a «ana» de otro (Fase 4). */
function limite_clave_usuario(string $usuario): string
{
    return 'u|' . tenant_actual()->slug . '|' . strtolower($usuario) . '|' . limite_ip();
}

function limite_ip(): string
{
    // Sin proxy inverso de confianza, REMOTE_ADDR es la única IP fiable.
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Lee/escribe el estado de una clave con bloqueo de archivo. */
function limite_actualizar(string $clave, callable $cambio): array
{
    $ruta = limite_dir() . DIRECTORY_SEPARATOR . hash('sha256', $clave) . '.json';
    $f = fopen($ruta, 'c+');
    if ($f === false) {
        return ['fallos' => [], 'bloqueado_hasta' => 0, 'reincidencias' => 0];
    }
    flock($f, LOCK_EX);
    $estado = json_decode((string)stream_get_contents($f), true) ?: [];
    $estado += ['fallos' => [], 'bloqueado_hasta' => 0, 'reincidencias' => 0];
    $ahora = time();
    $estado['fallos'] = array_values(array_filter($estado['fallos'], fn ($t) => $t > $ahora - LIMITE_VENTANA));
    $estado = $cambio($estado, $ahora);
    ftruncate($f, 0);
    rewind($f);
    fwrite($f, json_encode($estado));
    flock($f, LOCK_UN);
    fclose($f);
    return $estado;
}

/** Segundos de bloqueo restantes para este usuario/IP (0 = puede intentar). */
function limite_bloqueo_restante(string $usuario): int
{
    $ahora = time();
    $max = 0;
    foreach ([limite_clave_usuario($usuario), 'ip|' . limite_ip()] as $clave) {
        $e = limite_actualizar($clave, fn ($e) => $e);
        $max = max($max, $e['bloqueado_hasta'] - $ahora);
    }
    return max(0, $max);
}

function limite_registrar_fallo(string $usuario): void
{
    $registrar = function (int $umbral, bool $escalar) {
        return function (array $e, int $ahora) use ($umbral, $escalar) {
            $e['fallos'][] = $ahora;
            if (count($e['fallos']) >= $umbral) {
                $dur = $escalar
                    ? min(LIMITE_BLOQUEO_MAX, LIMITE_BLOQUEO_BASE * (2 ** $e['reincidencias']))
                    : LIMITE_BLOQUEO_BASE;
                $e['bloqueado_hasta'] = $ahora + $dur;
                $e['reincidencias']++;
                $e['fallos'] = [];
                error_log(sprintf('Login bloqueado %ds (ip=%s)', $dur, limite_ip()));
            }
            return $e;
        };
    };
    limite_actualizar(limite_clave_usuario($usuario), $registrar(LIMITE_FALLOS_USUARIO, true));
    limite_actualizar('ip|' . limite_ip(), $registrar(LIMITE_FALLOS_IP, false));
}

/**
 * Límite simple por IP para formularios públicos (H-14): true si se permite
 * y registra el uso; false si la IP superó $max usos en $ventana segundos.
 * Por institución (Fase 4): el formulario de un colegio no agota el de otro.
 */
function limite_publico(string $accion, int $max, int $ventana): bool
{
    $permitido = true;
    limite_actualizar('pub|' . tenant_actual()->slug . '|' . $accion . '|' . limite_ip(), function (array $e, int $ahora) use ($max, $ventana, &$permitido) {
        $e['usos'] = array_values(array_filter($e['usos'] ?? [], fn ($t) => $t > $ahora - $ventana));
        if (count($e['usos']) >= $max) {
            $permitido = false;
        } else {
            $e['usos'][] = $ahora;
        }
        return $e;
    });
    return $permitido;
}

function limite_registrar_exito(string $usuario): void
{
    limite_actualizar(
        limite_clave_usuario($usuario),
        fn ($e) => ['fallos' => [], 'bloqueado_hasta' => 0, 'reincidencias' => 0]
    );
}
