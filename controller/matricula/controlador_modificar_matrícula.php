<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Matricula\DatosMatricula;
use App\Repositories\PdoMatriculaRepositorio;
use App\Services\GestionarMatriculas;

// Respuesta: 1 = modificada, 2 = el alumno ya tiene matrícula ese año, 0 = datos inválidos o no existe.
// «estu» ya no se usa: el alumno de una matrícula no cambia al editarla.
try {
    $datos = DatosMatricula::desdeFormulario($_POST);
} catch (InvalidArgumentException) {
    exit('0');
}
$matriculas = new GestionarMatriculas(new PdoMatriculaRepositorio((new conexionBD())->conexionPDO()));
echo $matriculas->modificar((int) ($_POST['id'] ?? 0), $datos);
