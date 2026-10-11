<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR', 'SECRETARIA');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\DocentesPorUnidad;

/**
 * Docente de cada unidad didáctica por periodo (Fase 5.9).
 *   GET                              → catálogos: programas, periodos y docentes activos
 *   GET programa=…&periodo=…         → las unidades activas del programa con su docente en el periodo
 *   POST unidad, periodo, docente    → asigna (docente 0: quita la asignación)
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$docentes = new DocentesPorUnidad($pdo);
$entero = static fn (array $origen, string $c): int => preg_match('/^\d{1,9}$/', (string) ($origen[$c] ?? '')) === 1 ? (int) $origen[$c] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['programa'])) {
        echo json_encode($docentes->asignaciones($entero($_GET, 'programa'), $entero($_GET, 'periodo')), JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'programas' => $pdo->query("SELECT id_programa AS id, CONCAT(codigo, ' · ', nombre) AS nombre FROM programas_estudio WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        'periodos' => $pdo->query("SELECT p.id_periodo AS id, CONCAT(a.Nombre_año, ' · ', p.periodos) AS nombre
            FROM periodos p JOIN año_escolar a ON a.Id_año_escolar = p.id_año_escolar ORDER BY p.fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC),
        'docentes' => $pdo->query("SELECT Id_docente AS id, CONCAT_WS(' ', docente_apelli, docente_nombre) AS nombre
            FROM docentes WHERE docente_estatus = 'ACTIVO' ORDER BY docente_apelli, docente_nombre")->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$codigo = $docentes->asignar($entero($_POST, 'unidad'), $entero($_POST, 'periodo'), $entero($_POST, 'docente'));
echo json_encode(['codigo' => $codigo, 'mensaje' => $codigo === 1 ? 'Asignación guardada.' : 'La unidad, el periodo o el docente no existe, o el docente está inactivo.'], JSON_UNESCAPED_UNICODE);
