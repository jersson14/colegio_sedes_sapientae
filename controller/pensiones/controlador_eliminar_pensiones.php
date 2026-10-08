<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// Respuesta: 1 = eliminada, 0 = tiene pagos o no existe (antes: 500).
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->eliminar($_POST['id'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
