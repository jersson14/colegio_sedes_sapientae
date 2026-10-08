<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoAsistenciaRepositorio;
use App\Services\GestionarAsistencia;
use App\Support\Lote;

// Respuesta: 1 = todas registradas, 2 = alguna ya existía ese día (no se toca), 0 = datos inválidos
// o error (no se registra ninguna).
try {
    $asistencia = new GestionarAsistencia(new PdoAsistenciaRepositorio((new conexionBD())->conexionPDO()));
    echo $asistencia->registrar(Lote::desdeJson($_POST['registros'] ?? ''));
} catch (InvalidArgumentException) {
    echo 0;
} catch (PDOException $e) {
    error_log('Registrar asistencia: ' . $e->getMessage()); // p. ej. matrícula inexistente
    echo 0;
}
