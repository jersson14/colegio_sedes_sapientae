<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoAsistenciaRepositorio;
use App\Services\GestionarAsistencia;

// Borra la asistencia de un aula en un día. Respuesta: 1 = hecho, 0 = fecha o aula inválidas.
try {
    $asistencia = new GestionarAsistencia(new PdoAsistenciaRepositorio((new conexionBD())->conexionPDO()));
    $asistencia->eliminarDelDia($_POST['fecha'] ?? '', $_POST['aula'] ?? '');
    echo 1;
} catch (InvalidArgumentException) {
    echo 0;
}
