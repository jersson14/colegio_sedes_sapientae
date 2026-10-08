<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Alumno\FichaAlumno;
use App\Services\FabricaAlumnos;

// Respuesta: 1 = modificado, 2 = el DNI pertenece a otro alumno, 0 = datos inválidos. Foto inválida: 422.
// «idpa» y «fotoactual» del formulario ya no se usan: los padres y la foto actual salen de la BD.
try {
    $ficha = FichaAlumno::desdeFormulario($_POST);
} catch (InvalidArgumentException) {
    exit('0');
}
$id = (int) ($_POST['id'] ?? 0);
$fotoNueva = ($_POST['nombrefoto'] ?? '') !== '';
$alumnos = FabricaAlumnos::gestionar((new conexionBD())->conexionPDO());
echo $alumnos->modificar($id, $ficha, $fotoNueva) ? 1 : 2;
