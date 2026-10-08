<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1 = eliminada (y sus archivos), 0 = tiene entregas enviadas o calificadas.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $id = (string) ($_POST['id'] ?? '');
    exigir_tarea_propia($id); // IDOR
    echo $tareas->eliminar($id) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
