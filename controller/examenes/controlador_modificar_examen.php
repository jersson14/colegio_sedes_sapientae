<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1 = modificado, 2 = ya hay otro del curso en esa fecha, 0 = no existe o datos inválidos.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    exigir_examen_propio((string) ($_POST['id'] ?? '')); // IDOR
    exigir_curso_propio((string) ($_POST['asig'] ?? '')); // IDOR
    echo $tareas->modificarExamen($_POST);
} catch (InvalidArgumentException) {
    echo 0;
}
