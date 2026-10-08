<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ESTUDIANTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaTareas;

// Entrega del alumno. Respuesta: 1 = entregada, 0 = vencida, finalizada, ya calificada o sin archivo.
try {
    $tareas = FabricaTareas::gestionar((new conexionBD())->conexionPDO());
    $detalle = (string) ($_POST['iddetalle'] ?? '');
    // IDOR: el envío es del estudiante; la carpeta a reemplazar sale de la BD.
    $actual = exigir_envio_propio($detalle, (string) ($_POST['archivoactual'] ?? ''));
    echo $tareas->entregar($detalle, $actual, reemplazo: false) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
