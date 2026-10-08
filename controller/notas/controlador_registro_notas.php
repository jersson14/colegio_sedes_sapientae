<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'DOCENTE');
require_once __DIR__ . '/../../core/pertenencia.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Domain\Nota\Lote;
use App\Services\FabricaNotas;

// Respuesta: {"inserted_count": n, "status": s} con s = 1 todas insertadas, 2 alguna ya existía
// (no se sobrescribe), 0 datos inválidos (nota fuera de escala, texto demasiado largo…) o error.
$responder = static function (int $insertadas, int $estado): never {
    exit(json_encode(['inserted_count' => $insertadas, 'status' => $estado]));
};

try {
    $registros = Lote::desdeJson($_POST['registros'] ?? '');
} catch (InvalidArgumentException) {
    $responder(0, 0);
}
// IDOR: un docente solo pone notas en criterios de sus cursos y a alumnos de esas aulas (403 si no).
exigir_notas_propias($registros);

try {
    $insertadas = FabricaNotas::gestionar((new conexionBD())->conexionPDO())->registrar($registros);
} catch (InvalidArgumentException) {
    $responder(0, 0);
} catch (PDOException $e) {
    error_log('Registrar notas: ' . $e->getMessage()); // p. ej. matrícula inexistente
    $responder(0, 0);
}
$responder($insertadas, $insertadas === count($registros) ? 1 : 2);
