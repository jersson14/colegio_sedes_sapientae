<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE', 'AUXILIAR');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Publica una tarea con su enunciado. Respuesta: 1 = publicada, 2 = ya hay una con ese tema hoy,
// 0 = datos inválidos. Archivo no admitido: 422 (antes de crear nada).
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    exigir_curso_propio((string) ($_POST['asig'] ?? '')); // IDOR: curso del docente
    echo $tareas->publicar($_POST);
} catch (InvalidArgumentException) {
    echo 0;
}
