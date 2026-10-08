<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoUsuarioRepositorio;
use App\Services\GestionarCuentas;

$cuentas = new GestionarCuentas(new PdoUsuarioRepositorio((new conexionBD())->conexionPDO()));

// Respuesta: 1 = cambiado, 0 = estado o id inválidos (antes un estado inválido se guardaba vacío).
try {
    $cuentas->cambiarEstado((int) ($_POST['id'] ?? 0), (string) ($_POST['estatus'] ?? ''));
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
