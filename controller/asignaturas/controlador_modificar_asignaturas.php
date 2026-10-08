<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;

// Respuesta: 1 = modificada, 2 = ya existe otra con ese nombre en el aula, 0 = datos inválidos.
try {
    $horarios = FabricaHorarios::gestionar((new conexionBD())->conexionPDO());
    echo $horarios->modificarAsignatura($_POST['id'] ?? '', $_POST['asigna'] ?? '', $_POST['grado'] ?? '', $_POST['observa'] ?? '') ? 1 : 2;
} catch (InvalidArgumentException) {
    echo 0;
}
