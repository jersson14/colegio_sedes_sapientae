<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Respuesta: 1 = modificada, 2 = choca con otra tarea del curso, 0 = datos inválidos.
// La carpeta a reemplazar sale de la BD; se borra solo si la BD aceptó el cambio.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    exigir_curso_propio((string) ($_POST['asig'] ?? '')); // IDOR
    $actual = exigir_tarea_propia((string) ($_POST['id'] ?? ''), (string) ($_POST['archivoactual'] ?? ''));
    echo $tareas->modificar($_POST, $actual);
} catch (InvalidArgumentException) {
    echo 0;
}
