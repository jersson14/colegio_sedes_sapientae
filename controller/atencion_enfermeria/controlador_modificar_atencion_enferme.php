<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ENFERMERA');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Salud\TipoAtencion;
use App\Services\FabricaBienestar;

// Respuesta: 1 = modificada, 0 = no existe, no es de enfermería o datos inválidos.
// Una atención psicológica no se puede editar desde aquí (antes sí, con solo enviar su id).
try {
    $bienestar = FabricaBienestar::gestionar((new conexionBD())->conexionPDO());
    echo $bienestar->modificarAtencion(TipoAtencion::Enfermeria, $_POST) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
