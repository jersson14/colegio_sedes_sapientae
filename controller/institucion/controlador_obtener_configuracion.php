<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\PdoConfiguracionRepositorio;

// Configuración académica para el formulario del administrador (Fase 5.1).
$configuracion = (new PdoConfiguracionRepositorio((new conexionBD())->conexionPDO()))->cargar();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['tipo' => $configuracion->tipo()->etiqueta(), 'opciones' => $configuracion->todo()], JSON_UNESCAPED_UNICODE);
