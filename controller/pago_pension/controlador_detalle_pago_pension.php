<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// Cobro de una o varias filas (listas separadas por comas). Respuesta: 1 = cobrado, 2 = alguna ya estaba
// pagada (no se cobra ninguna; antes quedaban cobradas las anteriores), 0 = datos inválidos.
// Los ingresos quedan a nombre de quien cobra: el usuario de la sesión.
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->cobrar($_POST, (int) $_SESSION['S_ID']) ? 1 : 2;
} catch (InvalidArgumentException) {
    echo 0;
}
