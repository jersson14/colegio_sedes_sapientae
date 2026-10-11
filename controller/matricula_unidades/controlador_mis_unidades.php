<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ESTUDIANTE');
require_once __DIR__ . '/../../model/model_conexion.php';
require_once __DIR__ . '/../../core/institucion.php';

use App\Institucion\Evaluacion\RecordAcademico;

/**
 * El avance del estudiante en sesión en cada programa de estudios (institutos): sus unidades con la nota que
 * cuenta, el promedio según la institución, créditos aprobados y cargos. Solo lectura; el alumno sale de la
 * sesión (matricula.usu_id), nunca de la petición.
 *   GET → [{alumno, id_alumno, programa, id_programa, resumen, unidades}]
 */

header('Content-Type: application/json; charset=utf-8');
$pdo = (new conexionBD())->conexionPDO();
$configuracion = institucion_config();
$programas = $pdo->prepare(
    "SELECT DISTINCT al.Id_alumno AS id_alumno, CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) AS alumno,
            p.id_programa, p.nombre AS programa
       FROM matricula m
       JOIN alumnos al ON al.Id_alumno = m.id_alumno
       JOIN matricula_unidades mu ON mu.id_alumno = al.Id_alumno
       JOIN unidades_didacticas u ON u.id_unidad = mu.id_unidad
       JOIN modulos_formativos mo ON mo.id_modulo = u.id_modulo
       JOIN programas_estudio p ON p.id_programa = mo.id_programa
      WHERE m.usu_id = ?
      ORDER BY p.nombre"
);
$programas->execute([(int) $_SESSION['S_ID']]);
$record = new RecordAcademico($pdo);
$respuesta = [];
foreach ($programas->fetchAll(PDO::FETCH_ASSOC) as $f) {
    $detalle = $record->detalle((int) $f['id_alumno'], (int) $f['id_programa']);
    $respuesta[] = $f + [
        'resumen' => $record->de((int) $f['id_alumno'], (int) $f['id_programa'], $configuracion),
        'unidades' => array_map(static fn (array $u): array => [
            'periodo_academico' => (int) $u['periodo_academico'], 'codigo' => $u['codigo'], 'nombre' => $u['nombre'],
            'modulo' => $u['modulo'], 'creditos' => $u['creditos'], 'estado' => $u['estado'] ?? 'NO CURSADA',
            'nota' => $u['nota_recuperacion'] ?? $u['nota_final'], 'recuperacion' => $u['nota_recuperacion'] !== null, 'periodo' => $u['periodo'],
        ], $detalle['unidades']),
        'completo' => $detalle['completo'],
    ];
}
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
