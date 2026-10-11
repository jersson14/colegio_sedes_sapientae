<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Auditoria\RegistroAuditoria;

/**
 * Auditoría de la institución (H-15), solo lectura.
 *   GET                                               → los módulos con registros (para el filtro)
 *   GET buscar=1&desde=…&hasta=…&usuario=…&modulo=…   → hasta 500 registros, lo más reciente primero
 */

header('Content-Type: application/json; charset=utf-8');
$auditoria = new RegistroAuditoria((new conexionBD())->conexionPDO());
if (!isset($_GET['buscar'])) {
    echo json_encode(['modulos' => $auditoria->modulos()], JSON_UNESCAPED_UNICODE);
    exit;
}
$fecha = static fn (string $c): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET[$c] ?? '')) === 1 ? (string) $_GET[$c] : '';
echo json_encode($auditoria->buscar([
    'desde' => $fecha('desde'),
    'hasta' => $fecha('hasta'),
    'usuario' => mb_substr(trim((string) ($_GET['usuario'] ?? '')), 0, 100),
    'modulo' => mb_substr(trim((string) ($_GET['modulo'] ?? '')), 0, 60),
]), JSON_UNESCAPED_UNICODE);
