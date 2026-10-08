<?php

declare(strict_types=1);

require __DIR__ . '/../../model/model_conexion.php';
require __DIR__ . '/../../core/sesion.php';
require __DIR__ . '/../../core/limite_login.php';

use App\Domain\Usuario\ResultadoLogin;
use App\Repositories\PdoUsuarioRepositorio;
use App\Services\AutenticarUsuario;

// El usuario se escapa como siempre: así está guardado y así lo cuenta el límite de intentos.
$usuario = htmlspecialchars((string) ($_POST['u'] ?? ''), ENT_QUOTES, 'UTF-8');
$clave = (string) ($_POST['c'] ?? '');

// H-07: límite de intentos, antes de tocar la BD (429 + Retry-After).
$espera = limite_bloqueo_restante($usuario);
if ($espera > 0) {
    http_response_code(429);
    header('Retry-After: ' . $espera);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => 'Demasiados intentos fallidos. Intenta de nuevo en '
        . (int) ceil($espera / 60) . ' minuto(s).']));
}

$autenticar = new AutenticarUsuario(new PdoUsuarioRepositorio((new conexionBD())->conexionPDO()));
$resultado = $autenticar->ejecutar($usuario, $clave);

// Respuesta: 0 = credenciales incorrectas, 2 = usuario inactivo, 1 = sesión creada.
// Nunca se devuelve la fila (incluye el hash de la contraseña).
if ($resultado->resultado === ResultadoLogin::Incorrecto) {
    limite_registrar_fallo($usuario);
} elseif ($resultado->resultado === ResultadoLogin::Correcto && $resultado->cuenta !== null) {
    limite_registrar_exito($usuario);
    sesion_crear($resultado->cuenta);
}
echo $resultado->resultado->value;
