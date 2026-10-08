<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ENFERMERA');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Salud\TipoAtencion;
use App\Services\FabricaBienestar;

// Respuesta: 1 = registrada, 0 = datos inválidos. Quien atiende: el usuario de la sesión (antes el «idusu»).
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    $bienestar->registrarAtencion(TipoAtencion::Enfermeria, $_POST, (int) $_SESSION['S_ID']);
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
