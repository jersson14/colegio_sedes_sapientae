<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;
use App\Support\Lote;

// Respuesta: 1 = registrado, 2 = alguna clase ya estaba, 3 = una celda ya tiene otro curso,
// 4 = el docente ya tiene clase a esa hora, 0 = datos inválidos. Con 3 o 4 no se registra nada.
try {
    $horarios = FabricaHorarios::gestionar((new conexionBD())->conexionPDO());
    echo $horarios->registrar(Lote::desdeJson($_POST['componentes'] ?? ''));
} catch (InvalidArgumentException) {
    echo 0;
}
