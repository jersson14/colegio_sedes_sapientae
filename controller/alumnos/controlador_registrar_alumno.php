<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Alumno\FichaAlumno;
use App\Services\FabricaAlumnos;

// Respuesta: 1 = registrado, 2 = el DNI ya existe, 0 = datos inválidos. Foto inválida: 422.
// «nombrefoto» solo indica que hay foto nueva; el nombre lo genera el servidor.
try {
    $ficha = FichaAlumno::desdeFormulario($_POST);
} catch (InvalidArgumentException) {
    exit('0');
}
$alumnos = FabricaAlumnos::gestionar((new conexionBD())->conexionPDO());
echo $alumnos->registrar($ficha, ($_POST['nombrefoto'] ?? '') !== '') ? 1 : 2;
