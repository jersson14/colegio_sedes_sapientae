<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'ESTUDIANTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Services\FabricaAlumnos;

// IDOR: un estudiante solo cambia SU foto (DNI de la sesión). La foto a borrar sale de la BD.
// Sin foto nueva, el alumno queda sin foto. Respuesta: 1. Foto inválida: 422.
$dni = dni_propio(htmlspecialchars((string) ($_POST['id'] ?? ''), ENT_QUOTES, 'UTF-8'), 'ESTUDIANTE');
$alumnos = FabricaAlumnos::gestionar((new conexionBD())->conexionPDO());
$alumnos->cambiarFoto($dni, ($_POST['nombrefoto'] ?? '') !== '');
echo 1;
