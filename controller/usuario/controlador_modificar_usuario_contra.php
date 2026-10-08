<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoUsuarioRepositorio;
use App\Services\GestionarCuentas;

$cuentas = new GestionarCuentas(new PdoUsuarioRepositorio((new conexionBD())->conexionPDO()));

// Respuesta: 1 = cambiada, 0 = datos inválidos. La contraseña llega sin escapar: Contrasena la normaliza.
try {
    $cuentas->cambiarContrasena((int) ($_POST['id'] ?? 0), (string) ($_POST['con'] ?? ''));
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
