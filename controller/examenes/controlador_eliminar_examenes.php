<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $id = (string) ($_POST['id'] ?? '');
    exigir_examen_propio($id); // IDOR
    $tareas->eliminarExamen($id);
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
