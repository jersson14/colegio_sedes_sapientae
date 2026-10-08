<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Alumno\Texto;
use App\Services\FabricaAlumnos;

// Respuesta: 1 = eliminado, 0 = tiene matrícula o no existe (el panel muestra su aviso).
// El panel envía el DNI en el campo «id».
$alumnos = FabricaAlumnos::gestionar((new conexionBD())->conexionPDO());
echo $alumnos->eliminar(Texto::deFormulario($_POST['id'] ?? '')) ? 1 : 0;
