<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoUsuarioRepositorio;
use App\Services\GestionarCuentas;

$cuentas = new GestionarCuentas(new PdoUsuarioRepositorio((new conexionBD())->conexionPDO()));
$texto = static fn (string $campo): string => htmlspecialchars((string) ($_POST[$campo] ?? ''), ENT_QUOTES, 'UTF-8');

// Respuesta: 1 = actualizado, 2 = el nombre ya es de otra cuenta, 0 = datos inválidos.
// El usuario se guarda en mayúsculas, como en la matrícula (el login no distingue mayúsculas).
try {
    echo $cuentas->modificar((int) $texto('id'), strtoupper($texto('usu')), (int) $texto('rol'), $texto('correo')) ? 1 : 2;
} catch (InvalidArgumentException) {
    echo 0;
}
