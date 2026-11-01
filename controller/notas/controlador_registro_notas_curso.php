<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/regional.php';
require_once __DIR__ . '/../../utilitario/libreta.php';
require_once __DIR__ . '/../../utilitario/propiedad_docente.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_registro_tareas.php';
require_once __DIR__ . '/../../model/model_registro_notas.php';
// Registro de notas de un curso (estudiantes × criterios de un periodo): accion=cargar | guardar.
// El docente solo ve y califica sus cursos, y no cambia notas de un periodo finalizado; la dirección, cualquiera.
$MN = new Modelo_Registro_Notas();
$docente = rol_actual() === 'DOCENTE';
$ruta = 'controller/notas/controlador_registro_notas_curso.php';

/** Promedio de cada estudiante en el periodo (misma regla que la libreta). */
function rn_promedios(array $notas) {
    $res = [];
    foreach ($notas as $m => $por_criterio) {
        $res[$m] = libreta_promedio(array_column($por_criterio, 'nota'));
    }
    return $res;
}

if (($_POST['accion'] ?? 'cargar') === 'guardar') {
    $curso_id = entero_post('curso', 1, 'curso');
    exigir_curso_propio($curso_id, $ruta);
    $curso = $MN->Curso($curso_id);
    $periodo = $MN->Periodo(entero_post('periodo', 1, 'periodo'));
    if (!$curso || !$periodo || (int) $periodo['anio'] !== (int) $curso['anio']) {
        denegar(400, 'El periodo no es del año de ese curso');
    }
    if ($docente && $periodo['estado'] === 'FINALIZADO') {
        denegar(400, 'El ' . $periodo['nombre'] . ' ya está finalizado: pida a la dirección que corrija la nota');
    }
    $cambios = json_decode($_POST['cambios'] ?? '', true);
    if (!is_array($cambios) || !$cambios || count($cambios) > 3000) {
        denegar(400, 'No hay notas para guardar');
    }
    $criterios = array_map('intval', array_column($MN->Criterios($curso_id), 'id'));
    $matriculas = array_map('intval', array_column($MN->Estudiantes($curso['aula'], $curso['anio']), 'matricula'));
    $limpios = [];
    foreach ($cambios as $x) {
        $m = (int) ($x['matricula'] ?? 0);
        $cr = (int) ($x['criterio'] ?? 0);
        if (!in_array($m, $matriculas, true) || !in_array($cr, $criterios, true)) {
            registrar_acceso_denegado($ruta, 'NOTA_CURSO_AJENO');
            denegar(403, 'Solo puede registrar notas de los criterios del curso y de los estudiantes del aula');
        }
        $texto = trim((string) ($x['nota'] ?? ''));
        $nota = $texto === '' ? '' : nota_normalizada($texto);
        if ($nota === null) {
            denegar(400, 'Nota «' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '» fuera de la escala: use ' . descripcion_escala());
        }
        $conclusion = trim(strip_tags((string) ($x['conclusion'] ?? '')));
        if (mb_strlen($conclusion, 'UTF-8') > 255) {
            denegar(400, 'La conclusión admite hasta 255 caracteres');
        }
        $limpios[] = ['matricula' => $m, 'criterio' => $cr, 'nota' => $nota, 'conclusion' => $conclusion];
    }
    $guardadas = $MN->Guardar((int) $periodo['id'], $limpios);
    $notas = $MN->Notas($curso_id, (int) $periodo['id']);
    responder_json(['ok' => true, 'guardadas' => $guardadas, 'notas' => (object) $notas, 'promedios' => (object) rn_promedios($notas)]);
}

// Cargar: año, cursos del usuario, periodos, criterios, estudiantes y notas del periodo elegido
$anios = (new Modelo_Registro_Asistencia())->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) {
        if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
    }
}
$cursos = array_values(array_filter((new Modelo_Registro_Tareas())->Cursos($anio, $docente ? usuario_actual_id() : null),
    function ($c) { return stripos($c['asignatura'], 'RECREO') !== 0; }));
$curso = null;
foreach ($cursos as $c) {
    if ((int) $c['id'] === (int) ($_POST['curso'] ?? 0)) { $curso = $c; }
}
if (!empty($_POST['curso']) && !$curso) {
    registrar_acceso_denegado($ruta, 'CURSO_AJENO');
    denegar(403, 'Ese curso no está asignado a usted');
}
$curso = $curso ?: ($cursos[0] ?? null);
$periodos = $MN->Periodos($anio);
$periodo = null;
foreach ($periodos as $p) {
    if ((int) $p['id'] === (int) ($_POST['periodo'] ?? 0)) { $periodo = $p; }
}
if (!$periodo) {
    // el periodo en curso por fecha; si no, el último que ya empezó; si no, el primero
    $hoy = date('Y-m-d');
    foreach ($periodos as $p) {
        if ($p['fecha_inicio'] <= $hoy) { $periodo = $p; }
        if ($p['fecha_inicio'] <= $hoy && $hoy <= $p['fecha_fin']) { break; }
    }
    $periodo = $periodo ?: ($periodos[0] ?? null);
}
$criterios = [];
$estudiantes = [];
$notas = [];
if ($curso && $periodo) {
    $info = $MN->Curso($curso['id']);
    $criterios = $MN->Criterios($curso['id']);
    $estudiantes = $MN->Estudiantes($info['aula'], $info['anio']);
    $notas = $MN->Notas($curso['id'], (int) $periodo['id']);
}
responder_json([
    'ok' => true, 'anios' => $anios, 'anio' => $anio, 'cursos' => $cursos, 'curso' => $curso, 'periodos' => $periodos, 'periodo' => $periodo,
    'bloqueado' => $docente && $periodo && $periodo['estado'] === 'FINALIZADO',
    'criterios' => $criterios, 'estudiantes' => $estudiantes, 'notas' => (object) $notas, 'promedios' => (object) rn_promedios($notas),
    'escala' => descripcion_escala(),
]);
