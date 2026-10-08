<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaBienestar;

// Respuesta: 1 = eliminado (y su imagen), 0 = no existe.
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    echo $bienestar->eliminarComunicado($_POST['id'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
