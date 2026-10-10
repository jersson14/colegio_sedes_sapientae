<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Repositories\PdoEmpresaRepositorio;
use App\Services\GestionarPersonalizacion;

// Color y textos de la página pública, para el formulario «Personalizar» (Fase 4.7).
header('Content-Type: application/json; charset=utf-8');
echo json_encode((new GestionarPersonalizacion(new PdoEmpresaRepositorio((new conexionBD())->conexionPDO())))->formulario(), JSON_UNESCAPED_UNICODE);
