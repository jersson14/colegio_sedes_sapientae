<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// Respuesta: 1 = modificado (su ingreso válido sigue al monto), 0 = no existe o datos inválidos.
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->modificarPago($_POST['id'] ?? '', $_POST['monto'] ?? '', $_POST['descrip'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
