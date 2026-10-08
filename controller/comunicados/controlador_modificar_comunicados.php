<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaBienestar;

// Respuesta: 1 = modificado, 0 = no existe o datos inválidos. El autor no cambia; la imagen actual
// sale de la BD (antes del formulario).
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    echo $bienestar->modificarComunicado($_POST, ($_POST['nombrefoto'] ?? '') !== '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
