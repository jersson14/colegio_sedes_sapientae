<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;

// Borra el horario de un aula en un año escolar (antes, el de todos los años).
// Respuesta: 1 = hecho, 0 = aula o año inválidos.
try {
    FabricaHorarios::gestionar((new conexionBD())->conexionPDO())->eliminarDeAula($_POST['id'] ?? '', $_POST['anio'] ?? '');
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
