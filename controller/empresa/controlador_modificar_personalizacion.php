<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoEmpresaRepositorio;
use App\Services\GestionarPersonalizacion;

// Guarda el color y la página pública (Fase 4.7). 1 = guardado, 0 = sin empresa; 422 con el motivo
// si un campo no es válido. Los textos se guardan tal cual y se escapan al mostrarse (landing.php).
$campos = ['color', 'lema', 'bienvenida', 'nosotros_titulo', 'nosotros_texto', 'caracteristicas', 'niveles', 'valores', 'cifras', 'horario'];
$formulario = array_map(static fn (string $c): string => (string) ($_POST[$c] ?? ''), array_combine($campos, $campos));
try {
    $guardado = (new GestionarPersonalizacion(new PdoEmpresaRepositorio((new conexionBD())->conexionPDO())))->guardar($formulario);
} catch (InvalidArgumentException $e) {
    responder_error(422, $e->getMessage());
}
echo $guardado ? 1 : 0;
