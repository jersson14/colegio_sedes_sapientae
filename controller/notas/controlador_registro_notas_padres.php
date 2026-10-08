<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Support\Lote;
use App\Services\FabricaNotas;

// Respuesta: {"status": 1, "message", "inserted_count"} o {"status": 0, "message"}.
// Guardar otra vez una competencia la actualiza (antes se duplicaba).
$error = static function (string $mensaje): never {
    exit(json_encode(['status' => 0, 'message' => $mensaje]));
};

try {
    $registros = Lote::desdeJson($_POST['registros'] ?? '');
    $procesadas = FabricaNotas::gestionar((new conexionBD())->conexionPDO())->registrarDePadres($registros);
} catch (InvalidArgumentException $e) {
    $error($e->getMessage());
} catch (PDOException $e) {
    error_log('Registrar notas de padres: ' . $e->getMessage());
    $error('Error al registrar las notas de padres.');
}
echo json_encode([
    'status' => 1,
    'message' => 'Notas de padres registradas satisfactoriamente.',
    'inserted_count' => $procesadas,
]);
