<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;

// Respuesta: 1 = registrada, 2 = ya existe en el aula, 0 = datos inválidos.
try {
    $horarios = FabricaHorarios::gestionar((new conexionBD())->conexionPDO());
    echo $horarios->registrarAsignatura($_POST['asigna'] ?? '', $_POST['grado'] ?? '', $_POST['obse'] ?? '') ? 1 : 2;
} catch (InvalidArgumentException) {
    echo 0;
}
