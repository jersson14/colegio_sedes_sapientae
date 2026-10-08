<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoCajaRepositorio;
use App\Services\GestionarCaja;

// Respuesta: 1 = eliminado, 0 = está en uso o no existe (antes: 500).
try {
    $caja = new GestionarCaja(new PdoCajaRepositorio((new conexionBD())->conexionPDO()));
    echo $caja->eliminarIndicador($_POST['id'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
