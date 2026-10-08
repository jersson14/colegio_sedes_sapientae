<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoMatriculaRepositorio;
use App\Services\GestionarMatriculas;

// Respuesta: 1 = eliminada, 2 = tiene pensiones, ingresos válidos, notas, asistencias u otros
// registros (se conserva; los ingresos se anulan antes), 0 = no existe.
$matriculas = new GestionarMatriculas(new PdoMatriculaRepositorio((new conexionBD())->conexionPDO()));
echo $matriculas->eliminar((int) ($_POST['id'] ?? 0));
