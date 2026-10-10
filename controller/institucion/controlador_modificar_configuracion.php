<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\Configuracion;
use App\Institucion\PdoConfiguracionRepositorio;

/**
 * Guarda la configuración académica (Fase 5.1). 1 = guardado; 422 con el motivo si algo no es válido.
 * El tipo de institución no se cambia desde aquí: es parte de lo contratado (panel de superadministrador
 * o tools/configurar_institucion.php).
 */
$cambios = [];
foreach (array_keys(Configuracion::CLAVES) as $clave) {
    $campo = str_replace('.', '_', $clave); // PHP convierte los puntos de $_POST en guiones bajos
    if ($clave !== 'institucion.tipo' && array_key_exists($campo, $_POST)) {
        $cambios[$clave] = (string) $_POST[$campo];
    }
}
try {
    (new PdoConfiguracionRepositorio((new conexionBD())->conexionPDO()))->guardar($cambios);
} catch (InvalidArgumentException $e) {
    responder_error(422, $e->getMessage());
}
echo 1;
