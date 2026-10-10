<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Institucion\Evaluacion\RecordAcademico;
use App\Institucion\MatriculaPorUnidad;
use App\Institucion\PdoConfiguracionRepositorio;

/**
 * Matrícula por unidad didáctica (Fase 5.4).
 *   GET                                   → catálogos: alumnos, programas y periodos
 *   GET alumno=…&programa=…&periodo=…     → la situación del alumno en cada unidad del programa
 *   GET record=1&alumno=…&programa=…      → récord académico: promedio, créditos aprobados y cargos (Fase 5.5/5.6)
 *   POST accion=matricular (alumno, unidad, periodo) | retirar (id) | calificar (id, nota) | recuperar (id, nota)
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$matricula = new MatriculaPorUnidad($pdo);
$entero = static fn (array $origen, string $c): int => preg_match('/^\d{1,9}$/', (string) ($origen[$c] ?? '')) === 1 ? (int) $origen[$c] : 0;

$configuracion = (new PdoConfiguracionRepositorio($pdo))->cargar();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['record'])) {
        echo json_encode((new RecordAcademico($pdo))->de($entero($_GET, 'alumno'), $entero($_GET, 'programa'), $configuracion), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['alumno'])) {
        echo json_encode($matricula->situacion($entero($_GET, 'alumno'), $entero($_GET, 'programa'), $entero($_GET, 'periodo')), JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'alumnos' => $pdo->query("SELECT Id_alumno AS id, alum_dni AS dni, CONCAT_WS(' ', alum_apepat, alum_apemat, alum_nombre) AS nombre
            FROM alumnos WHERE alum_estatus = 'SI' ORDER BY alum_apepat, alum_apemat, alum_nombre")->fetchAll(PDO::FETCH_ASSOC),
        'programas' => $pdo->query("SELECT id_programa AS id, CONCAT(codigo, ' · ', nombre) AS nombre FROM programas_estudio WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        'periodos' => $pdo->query("SELECT p.id_periodo AS id, CONCAT(a.Nombre_año, ' · ', p.periodos) AS nombre
            FROM periodos p JOIN año_escolar a ON a.Id_año_escolar = p.id_año_escolar ORDER BY p.fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$accion = (string) ($_POST['accion'] ?? '');
if ($accion === 'matricular') {
    $codigo = $matricula->matricular($entero($_POST, 'alumno'), $entero($_POST, 'unidad'), $entero($_POST, 'periodo'));
    echo json_encode(['codigo' => $codigo, 'mensaje' => MatriculaPorUnidad::RESULTADOS[$codigo] ?? ''], JSON_UNESCAPED_UNICODE);
} elseif ($accion === 'retirar') {
    $ok = $matricula->retirar($entero($_POST, 'id'));
    echo json_encode(['codigo' => $ok ? 1 : 0, 'mensaje' => $ok ? 'Retirado de la unidad.' : 'Solo se retira una unidad en curso (sin nota).'], JSON_UNESCAPED_UNICODE);
} elseif ($accion === 'calificar' || $accion === 'recuperar') {
    $codigo = $matricula->registrarNota($accion, $entero($_POST, 'id'), trim((string) ($_POST['nota'] ?? '')), $configuracion);
    echo json_encode(['codigo' => $codigo, 'mensaje' => MatriculaPorUnidad::RESULTADOS_NOTA[$accion][$codigo] ?? ''], JSON_UNESCAPED_UNICODE);
} else {
    responder_error(422, 'Acción desconocida.');
}
