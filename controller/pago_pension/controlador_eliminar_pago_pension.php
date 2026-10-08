<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoPensionRepositorio;
use App\Services\GestionarPensiones;

// «Anular pago»: el ingreso se anula y queda en caja (antes se borraba) y la pensión se libera.
// Quién anula: el usuario de la sesión. Respuesta: 1 = anulado, 0 = no existe.
try {
    $pensiones = new GestionarPensiones(new PdoPensionRepositorio((new conexionBD())->conexionPDO()));
    echo $pensiones->anularPago($_POST['id'] ?? '', (int) $_SESSION['S_ID']) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
