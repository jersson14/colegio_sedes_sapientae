<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require_once __DIR__ . '/../../model/model_registro_asistencia.php';
require_once __DIR__ . '/../../model/model_registro_tareas.php';
require_once __DIR__ . '/../../model/model_aula_virtual.php';
// Aula virtual: accion=cursos (tarjetas) | curso (tablón, material, horario, personas y calificaciones de un curso).
// Estudiante: los cursos de su aula. Docente: los suyos. Dirección y auxiliar: los de un aula que eligen.
$MA = new Modelo_Registro_Asistencia();
$M = new Modelo_Aula_Virtual();
$rol = rol_actual();
$usu = usuario_actual_id();

/** Foto del docente solo si es un archivo de la carpeta de fotos; si no, la tarjeta muestra la inicial. */
function av_foto($f) {
    return preg_match('#^controller/docentes/fotos/[\w.-]+\.(jpe?g|png|gif|webp)$#i', (string) $f) && is_file(__DIR__ . '/../../' . $f) ? $f : '';
}

/** Material del curso con sus archivos (nombre, tamaño, si es video) y enlaces (con la dirección para verlo dentro, si se puede). */
function av_material(array $lista) {
    foreach ($lista as &$m) {
        $m['archivos'] = [];
        $dir = preg_match('/^mat_\d+$/', (string) $m['carpeta']) ? __DIR__ . '/material/' . $m['carpeta'] : '';
        if ($dir !== '' && is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if ($f[0] !== '.' && is_file($dir . '/' . $f)) {
                    $m['archivos'][] = ['nombre' => $f, 'tamano' => filesize($dir . '/' . $f), 'video' => es_video($f)];
                }
            }
        }
        $m['enlaces'] = array_map(function ($e) { return ['url' => $e['url'], 'video' => video_incrustado($e['url'])]; }, enlaces_video_lista($m['enlaces']));
        unset($m['carpeta']);
    }
    return $lista;
}

/** Próxima clase de cada curso a partir de ahora: [curso => ['dia' => 1..6, 'inicio', 'fin', 'hoy' => bool]]. */
function av_proximas(array $clases) {
    $hoy = (int) date('N');
    $ahora = date('H:i');
    $res = [];
    foreach ($clases as $c) {
        $dias = ($c['dia'] - $hoy + 7) % 7;
        if ($dias === 0 && $c['fin'] <= $ahora) { $dias = 7; }
        $clave = $dias * 10000 + (int) str_replace(':', '', $c['inicio']);
        if (!isset($res[$c['curso']]) || $clave < $res[$c['curso']]['_k']) {
            $res[$c['curso']] = ['dia' => $c['dia'], 'inicio' => $c['inicio'], 'fin' => $c['fin'], 'hoy' => $dias === 0, '_k' => $clave];
        }
    }
    foreach ($res as &$r) { unset($r['_k']); }
    return $res;
}

$anios = $MA->Anios();
$anio = (int) ($_POST['anio'] ?? 0);
if (!$anio) {
    foreach ($anios as $a) {
        if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
    }
}

if (($_POST['accion'] ?? 'cursos') === 'curso') {
    $curso = $M->Curso(entero_post('curso', 1, 'curso'));
    if (!$curso) {
        denegar(404, 'Curso no encontrado');
    }
    $matricula = null;
    if ($rol === 'ESTUDIANTE') {
        $mat = $M->Matricula_Estudiante($usu, $curso['anio']);
        if (!$mat || (int) $mat['aula'] !== (int) $curso['aula']) {
            registrar_acceso_denegado('controller/aula_virtual/controlador_aula_virtual.php', 'CURSO_AJENO');
            denegar(403, 'Ese curso no es de su aula');
        }
        $matricula = (int) $mat['matricula'];
        // Las tareas publicadas antes de su matrícula también le corresponden
        (new Modelo_Registro_Tareas())->Completar_Envios($curso['id']);
    } elseif ($rol === 'DOCENTE' && (int) $curso['usu_docente'] !== (int) $usu) {
        registrar_acceso_denegado('controller/aula_virtual/controlador_aula_virtual.php', 'CURSO_AJENO');
        denegar(403, 'Ese curso no está asignado a usted');
    } elseif (!in_array($rol, ['ADMINISTRADOR', 'AUXILIAR', 'DOCENTE', 'ESTUDIANTE'], true)) {
        denegar(403, 'No tiene permiso para esta acción');
    }
    $curso['foto'] = av_foto($curso['foto']);
    $publica = in_array($rol, ['ADMINISTRADOR', 'AUXILIAR'], true) || $rol === 'DOCENTE';   // el docente ya pasó el control de arriba
    unset($curso['usu_docente']);
    responder_json([
        'ok' => true, 'curso' => $curso, 'puede_publicar' => $publica,
        'material' => av_material($M->Materiales($curso['id'])),
        'tareas' => $M->Tareas($curso['id'], $matricula),
        'horario' => $M->Clases($curso['anio'], ['curso' => $curso['id']]),
        'estudiantes' => $M->Estudiantes($curso['aula'], $curso['anio']),
        'ahora' => date('Y-m-d H:i:s'),
    ]);
}

$aulas = [];
$aula = 0;
$matricula = null;
if ($rol === 'ESTUDIANTE') {
    $mat = $M->Matricula_Estudiante($usu, $anio);
    $matricula = $mat ? (int) $mat['matricula'] : null;
    $quien = ['aula' => $mat ? (int) $mat['aula'] : -1];
    $clases = $mat ? $M->Clases($anio, ['aula' => $quien['aula']]) : [];
} elseif ($rol === 'DOCENTE') {
    $quien = ['docente' => $usu];
    $clases = $M->Clases($anio, ['docente' => $usu]);
} else {
    $aulas = $M->Aulas($anio);
    $aula = (int) ($_POST['aula'] ?? 0) ?: (int) ($aulas[0]['id'] ?? 0);
    if (!in_array($aula, array_map('intval', array_column($aulas, 'id')), true)) {
        $aula = (int) ($aulas[0]['id'] ?? 0);
    }
    $quien = ['aula' => $aula ?: -1];
    $clases = $aula ? $M->Clases($anio, ['aula' => $aula]) : [];
}
$proximas = av_proximas($clases);
// El recreo figura como curso en el horario, pero no tiene aula virtual
$cursos = array_values(array_filter($M->Cursos($anio, $quien, $matricula), function ($c) { return stripos($c['asignatura'], 'RECREO') !== 0; }));
foreach ($cursos as &$c) {
    $c['foto'] = av_foto($c['foto']);
    $c['proxima'] = $proximas[$c['id']] ?? null;
    unset($c['usu_docente']);
}
unset($c);
responder_json(['ok' => true, 'anios' => $anios, 'anio' => $anio, 'aulas' => $aulas, 'aula' => $aula, 'cursos' => $cursos, 'sin_matricula' => $rol === 'ESTUDIANTE' && !$matricula]);
