<?php
/**
 * Reglas de contraseñas (Fase 13). Requiere controller/_seguridad.php (denegar).
 * Se guardan como password_hash(htmlspecialchars(clave)), igual que siempre, para que el ingreso siga funcionando.
 */
require_once __DIR__ . '/../model/model_conexion.php';

const CLAVE_MIN = 8;

/** Valida la contraseña escrita y devuelve su hash; corta con 400 y el motivo si no sirve. */
function clave_post($campo, $usuario = '', $etiqueta = 'La contraseña') {
    $clave = (string) ($_POST[$campo] ?? '');
    if (mb_strlen($clave, 'UTF-8') < CLAVE_MIN) {
        denegar(400, $etiqueta . ' debe tener al menos ' . CLAVE_MIN . ' caracteres');
    }
    if ($usuario !== '' && strcasecmp(trim($clave), trim($usuario)) === 0) {
        denegar(400, $etiqueta . ' no puede ser igual al nombre de usuario');
    }
    return password_hash(htmlspecialchars($clave, ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT, ['cost' => 12]);
}

/** Marca la contraseña de un usuario como temporal (debe cambiarla al ingresar) o no. */
function marcar_clave_temporal($usu_id = null, $usu_usuario = null, $temporal = 1) {
    $pdo = (new conexionBD())->conexionPDO();
    if ($usu_id !== null) {
        $pdo->prepare("UPDATE usuario SET debe_cambiar_clave = ? WHERE usu_id = ?")->execute([$temporal, $usu_id]);
    } elseif ($usu_usuario !== null) {
        $pdo->prepare("UPDATE usuario SET debe_cambiar_clave = ? WHERE usu_usuario = ?")->execute([$temporal, $usu_usuario]);
    }
}

/** Contraseña temporal fácil de dictar: sin letras ni números que se confunden (0/O, 1/l/I). */
function clave_temporal() {
    $letras = 'abcdefghjkmnpqrstuvwxyz';
    $numeros = '23456789';
    $c = '';
    for ($i = 0; $i < 6; $i++) { $c .= $letras[random_int(0, strlen($letras) - 1)]; }
    for ($i = 0; $i < 3; $i++) { $c .= $numeros[random_int(0, strlen($numeros) - 1)]; }
    return ucfirst($c);
}
