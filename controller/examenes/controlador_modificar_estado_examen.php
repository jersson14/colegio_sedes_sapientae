<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1 = cambiado, 0 = estado inválido o examen inexistente.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $id = (string) ($_POST['id'] ?? '');
    exigir_examen_propio($id); // IDOR
    echo $tareas->estadoExamen($id, $_POST['estatus'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
