<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('DOCENTE');
require_once __DIR__ . '/../../model/model_conexion.php';
require_once __DIR__ . '/../../core/pertenencia.php';

use App\Institucion\DocentesPorUnidad;
use App\Institucion\MatriculaPorUnidad;
use App\Institucion\PdoConfiguracionRepositorio;

/**
 * Las unidades didácticas del docente en sesión (Fase 5.9): solo las que tiene asignadas en el periodo.
 *   GET                            → periodos
 *   GET periodo=…                  → sus unidades en ese periodo
 *   GET unidad=…&periodo=…         → los matriculados en esa unidad (403 si no la dicta)
 *   POST accion=calificar|recuperar (id, nota) → 403 si la matrícula no es de una unidad suya
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$docentes = new DocentesPorUnidad($pdo);
$docente = (int) id_docente_propio(null);
$entero = static fn (array $origen, string $c): int => preg_match('/^\d{1,9}$/', (string) ($origen[$c] ?? '')) === 1 ? (int) $origen[$c] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['unidad'])) {
        [$unidad, $periodo] = [$entero($_GET, 'unidad'), $entero($_GET, 'periodo')];
        if (!$docentes->dicta($docente, $unidad, $periodo)) {
            denegar_ajeno();
        }
        echo json_encode($docentes->matriculados($unidad, $periodo), JSON_UNESCAPED_UNICODE);
    } elseif (isset($_GET['periodo'])) {
        echo json_encode($docentes->unidadesDe($docente, $entero($_GET, 'periodo')), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['periodos' => $pdo->query("SELECT p.id_periodo AS id, CONCAT(a.Nombre_año, ' · ', p.periodos) AS nombre
            FROM periodos p JOIN año_escolar a ON a.Id_año_escolar = p.id_año_escolar ORDER BY p.fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$accion = (string) ($_POST['accion'] ?? '');
if ($accion !== 'calificar' && $accion !== 'recuperar') {
    responder_error(422, 'Acción desconocida.');
}
$id = $entero($_POST, 'id');
if (!$docentes->dictaMatricula($docente, $id)) {
    denegar_ajeno();
}
$configuracion = (new PdoConfiguracionRepositorio($pdo))->cargar();
$codigo = (new MatriculaPorUnidad($pdo))->registrarNota($accion, $id, trim((string) ($_POST['nota'] ?? '')), $configuracion);
echo json_encode(['codigo' => $codigo, 'mensaje' => MatriculaPorUnidad::RESULTADOS_NOTA[$accion][$codigo] ?? ''], JSON_UNESCAPED_UNICODE);
