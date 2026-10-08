<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// Respuesta: 1 = modificada, 2 = ya hay otra de ese nivel, mes y año, 0 = no existe o datos inválidos.
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->modificar($_POST);
} catch (InvalidArgumentException) {
    echo 0;
}
