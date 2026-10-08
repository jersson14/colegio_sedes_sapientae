<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Nota\Lote;
use App\Services\FabricaNotas;

// Respuesta: {"status": 1|2, "message", "errores": [...]}; 2 si alguna nota no se pudo actualizar.
header('Content-Type: application/json');
try {
    $registros = Lote::desdeJson($_POST['registros'] ?? '');
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(json_encode(['error' => $e->getMessage()]));
}
$r = FabricaNotas::gestionar((new conexionBD())->conexionPDO())->editarDePadres($registros);
$ok = $r['errores'] === [];
echo json_encode([
    'status' => $ok ? 1 : 2,
    'message' => $ok ? 'Todas las notas se actualizaron correctamente, tanto las notas del estudiante como de los padres'
                     : "Hubo errores al actualizar algunas notas ({$r['actualizadas']} actualizaciones exitosas)",
    'errores' => $r['errores'],
]);
