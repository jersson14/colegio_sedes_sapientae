<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;
use App\Repositories\PdoMatriculaRepositorio;
use App\Services\GestionarMatriculas;

// Respuesta: 1 = matriculado, 2 = ya matriculado ese año, 3 = el usuario ya existe, 0 = datos inválidos.
// Los ingresos quedan a nombre de quien cobra: el usuario de la sesión.
try {
    $datos = DatosMatricula::desdeFormulario($_POST);
    $cuenta = CuentaNueva::desdeFormulario($_POST);
} catch (InvalidArgumentException) {
    exit((string) ResultadoRegistro::Invalida->value);
}
$matriculas = new GestionarMatriculas(new PdoMatriculaRepositorio((new conexionBD())->conexionPDO()));
echo $matriculas->registrar((int) ($_POST['estu'] ?? 0), $datos, $cuenta, (int) $_SESSION['S_ID'])->value;
