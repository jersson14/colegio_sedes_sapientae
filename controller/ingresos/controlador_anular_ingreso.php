<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Caja\Movimiento;
use App\Repositories\PdoCajaRepositorio;
use App\Services\AnularMovimiento;

// Respuesta: 1 = anulado, 0 = no existe, ya estaba anulado o falta el motivo.
// Quién anula sale de la sesión (antes llegaba en «usu» y reemplazaba a quien cobró).
try {
    $anular = new AnularMovimiento(new PdoCajaRepositorio((new conexionBD())->conexionPDO()));
    echo $anular->ejecutar(Movimiento::Ingreso, $_POST['id'] ?? '', $_POST['obser'] ?? '', (int) $_SESSION['S_ID']) ? 1 : 0;
} catch (InvalidArgumentException) {
    echo 0;
}
