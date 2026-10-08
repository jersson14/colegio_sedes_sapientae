<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoAsistenciaRepositorio;
use App\Services\GestionarAsistencia;
use App\Support\Lote;

// Respuesta: 1 = editadas, 2 = alguna no existe, 0 = datos inválidos (no se edita ninguna).
// La fecha no cambia aunque llegue en el registro.
try {
    $asistencia = new GestionarAsistencia(new PdoAsistenciaRepositorio((new conexionBD())->conexionPDO()));
    echo $asistencia->editar(Lote::desdeJson($_POST['registros'] ?? ''));
} catch (InvalidArgumentException) {
    echo 0;
}
