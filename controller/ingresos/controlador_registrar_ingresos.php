<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Caja\Movimiento;
use App\Repositories\PdoCajaRepositorio;
use App\Services\GestionarCaja;

// Respuesta: 1 = registrado, 0 = el indicador no es de ingresos o datos inválidos.
// Quién cobra: el usuario de la sesión (antes el «usu» del formulario).
try {
    $caja = new GestionarCaja(new PdoCajaRepositorio((new conexionBD())->conexionPDO()));
    echo $caja->registrar(Movimiento::Ingreso, $_POST, (int) $_SESSION['S_ID']) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
