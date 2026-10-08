<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'PSICOLOGA');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Salud\TipoAtencion;
use App\Services\FabricaBienestar;

// Respuesta: 1 = modificada, 0 = no existe, no es psicológica o datos inválidos.
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    echo $bienestar->modificarAtencion(TipoAtencion::Psicologia, $_POST) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
