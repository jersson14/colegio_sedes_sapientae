<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Solo se finaliza (quien no entregó queda con 5). Respuesta: 1 = finalizada, 0 = otro estado.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $id = (string) ($_POST['id'] ?? '');
    exigir_tarea_propia($id); // IDOR
    echo $tareas->cambiarEstado($id, $_POST['estatus'] ?? '') ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
