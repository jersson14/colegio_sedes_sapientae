<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1 = calificada, 0 = nota fuera de 0–20 o entrega inexistente.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $id = (string) ($_POST['id'] ?? '');
    exigir_envio_calificable($id); // IDOR: envío de una tarea suya
    echo $tareas->calificar($id, $_POST['nota'] ?? '', $_POST['obser'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
