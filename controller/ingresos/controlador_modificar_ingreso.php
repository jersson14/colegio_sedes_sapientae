<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Caja\Movimiento;
use App\Repositories\PdoCajaRepositorio;
use App\Services\GestionarCaja;

// Respuesta: 1 = modificado, 0 = no existe, está anulado, es de un pago de pensión (se edita el pago)
// o datos inválidos. Quién cobró no cambia (antes lo reemplazaba el «usu» del formulario).
try {
    $caja = new GestionarCaja(new PdoCajaRepositorio((new conexionBD())->conexionPDO()));
    echo $caja->modificar(Movimiento::Ingreso, $_POST) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
