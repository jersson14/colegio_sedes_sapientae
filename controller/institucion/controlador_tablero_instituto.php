<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'SECRETARIA');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\TableroInstituto;

/** Tablero de inicio de un instituto (semestre en curso y alertas). GET → JSON. */
header('Content-Type: application/json; charset=utf-8');
$resumen = (new TableroInstituto((new conexionBD())->conexionPDO()))->resumen();
if (($_SESSION['S_ROL'] ?? '') !== 'ADMINISTRADOR') {
    unset($resumen['ingresos_del_mes']); // la caja es del administrador
}
echo json_encode($resumen, JSON_UNESCAPED_UNICODE);
