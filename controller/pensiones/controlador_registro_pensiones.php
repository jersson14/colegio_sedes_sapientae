<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// Respuesta: 1 = registrada, 2 = ya existe la de ese nivel, mes y año, 0 = datos inválidos.
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->registrar($_POST);
} catch (InvalidArgumentException) {
    echo 0;
}
