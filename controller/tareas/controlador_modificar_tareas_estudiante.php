<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ESTUDIANTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Reemplazo de la entrega. Respuesta: 1 = reemplazada, 0 = vencida, finalizada, ya calificada o sin archivo.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $detalle = (string) ($_POST['iddetalle'] ?? '');
    $actual = exigir_envio_propio($detalle, (string) ($_POST['archivoactual'] ?? '')); // IDOR
    echo $tareas->entregar($detalle, $actual, reemplazo: true) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
