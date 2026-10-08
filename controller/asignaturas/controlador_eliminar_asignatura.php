<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;

// Respuesta: 1 = eliminada, 0 = tiene docente asignado o no existe (antes: 500).
try {
    echo FabricaHorarios::gestionar((new conexionBD())->conexionPDO())->eliminarAsignatura($_POST['id'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
