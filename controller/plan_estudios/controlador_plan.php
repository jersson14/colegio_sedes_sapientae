<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\PlanDeEstudios;
use App\Institucion\UnidadDidactica;

/**
 * Plan de estudios de un instituto (Fase 5.3).
 *   GET                         → el plan completo (programas → módulos → unidades con prerrequisitos)
 *   POST accion=guardar_programa|eliminar_programa|guardar_modulo|eliminar_modulo|guardar_unidad|eliminar_unidad
 *        |agregar_requisito|quitar_requisito
 * Responde JSON: {"ok": true, "id": …} o 422 {"error": motivo}.
 */

header('Content-Type: application/json; charset=utf-8');
$plan = new PlanDeEstudios((new conexionBD())->conexionPDO());
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($plan->arbol(), JSON_UNESCAPED_UNICODE);
    exit;
}

$entero = static fn (string $c): int => preg_match('/^\d{1,9}$/', (string) ($_POST[$c] ?? '')) === 1 ? (int) $_POST[$c] : 0;
$texto = static fn (string $c): string => trim((string) ($_POST[$c] ?? ''));
try {
    $resultado = match ((string) ($_POST['accion'] ?? '')) {
        'guardar_programa' => ['id' => $plan->guardarPrograma($entero('id'), $texto('codigo'), $texto('nombre'), $texto('estado') !== 'INACTIVO')],
        'guardar_modulo' => ['id' => $plan->guardarModulo($entero('id'), $entero('programa'), $texto('nombre'), $entero('orden'))],
        'guardar_unidad' => ['id' => $plan->guardarUnidad(UnidadDidactica::desdeFormulario($_POST))],
        'eliminar_programa', 'eliminar_modulo', 'eliminar_unidad' => $plan->eliminar(substr((string) $_POST['accion'], 9), $entero('id'))
            ? []
            : throw new DomainException('No se puede eliminar: tiene contenido o historial (desactívalo en su lugar).'),
        'agregar_requisito' => (static function () use ($plan, $entero): array {
            $plan->agregarPrerrequisito($entero('unidad'), $entero('requisito'));
            return [];
        })(),
        'quitar_requisito' => ['quitado' => $plan->quitarPrerrequisito($entero('unidad'), $entero('requisito'))],
        default => throw new InvalidArgumentException('Acción desconocida.'),
    };
} catch (InvalidArgumentException | DomainException $e) {
    responder_error(422, $e->getMessage());
}
echo json_encode(['ok' => true] + $resultado, JSON_UNESCAPED_UNICODE);
