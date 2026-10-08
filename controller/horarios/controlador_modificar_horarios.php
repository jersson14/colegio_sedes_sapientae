<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'AUXILIAR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaHorarios;
use App\Support\Lote;

// Al editar, el panel quita filas con eliminar_horario_unico y envía aquí todas las que quedan:
// se añaden las nuevas. Respuesta: 1 = se añadió alguna, 2 = ya estaban todas, 3/4 = choque (no se
// añade ninguna), 0 = datos inválidos.
try {
    $horarios = FabricaHorarios::gestionar((new conexionBD())->conexionPDO());
    echo $horarios->registrar(Lote::desdeJson($_POST['componentes'] ?? ''), alEditar: true);
} catch (InvalidArgumentException) {
    echo 0;
}
