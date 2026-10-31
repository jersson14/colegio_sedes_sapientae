<?php
/**
 * Libreta de notas (Fase 13, paso 9): nota de cada curso por periodo, promedio, estado y orden de mérito del aula.
 * La usan la pantalla de libretas (controller/boletas/controlador_libreta.php) y el PDF (view/MPDF/REPORTE/libreta.php),
 * así ambos muestran siempre lo mismo. Requiere la sesión iniciada y utilitario/regional.php.
 *
 * Reglas, con la escala de Configuración → Datos regionales:
 * - Nota del curso en un periodo = promedio de las notas de sus criterios.
 *   Numéricas: promedio redondeado a los decimales configurados. Literales (p. ej. AD, A, B, C): cada letra vale
 *   su posición (la mejor vale más), se promedia y se redondea a la letra más cercana.
 *   Si un curso mezcla números y letras, los números se pasan a la letra de su tramo de la escala.
 * - Nota anual del curso = promedio de sus notas de periodo, con la misma regla.
 * - Promedio general = promedio de las notas de los cursos (si todas son numéricas); para el orden de mérito cada
 *   nota se lleva a 0–1 dentro de la escala, así también se ordenan aulas con letras.
 */
require_once __DIR__ . '/../model/model_conexion.php';
require_once __DIR__ . '/regional.php';

function libreta_redondear($n) {
    $d = (int) config_regional()['nota_decimales'];
    return number_format(round($n + 1e-9, $d), $d, '.', '');
}

/** Valor 1..L de una letra (la primera de la lista es la mejor). */
function libreta_valor_letra($l) {
    $lista = notas_literales();
    $i = array_search($l, $lista, true);
    return $i === false ? null : count($lista) - $i;
}

/** Letra del tramo de la escala en que cae un número. */
function libreta_letra_de_numero($n) {
    $c = config_regional();
    $lista = notas_literales();
    $L = count($lista);
    $f = ($c['nota_max'] - $c['nota_min']) > 0 ? ((float) $n - $c['nota_min']) / ($c['nota_max'] - $c['nota_min']) : 0;
    $v = max(1, min($L, (int) ceil($f * $L)));
    return $lista[$L - $v];
}

/** Promedio de una lista de notas según la escala. Devuelve la nota (texto) o null si no hay notas válidas. */
function libreta_promedio(array $notas) {
    $vals = array_values(array_filter(array_map('nota_normalizada', $notas), function ($x) { return $x !== null; }));
    if (!$vals) { return null; }
    $numericas = array_filter($vals, 'is_numeric');
    if (count($numericas) === count($vals)) {
        return libreta_redondear(array_sum($vals) / count($vals));
    }
    // Con letras: los números se pasan a su letra y se promedian las posiciones
    $pos = array_map(function ($v) { return libreta_valor_letra(is_numeric($v) ? libreta_letra_de_numero($v) : $v); }, $vals);
    $lista = notas_literales();
    $prom = (int) round(array_sum($pos) / count($pos));
    return $lista[count($lista) - max(1, min(count($lista), $prom))];
}

/** Nota llevada a 0–1 dentro de la escala (para ordenar por mérito). */
function libreta_fraccion($nota) {
    if ($nota === null) { return null; }
    if (is_numeric($nota)) {
        $c = config_regional();
        $r = $c['nota_max'] - $c['nota_min'];
        return $r > 0 ? ((float) $nota - $c['nota_min']) / $r : 0;
    }
    $L = count(notas_literales());
    return $L > 1 ? (libreta_valor_letra($nota) - 1) / ($L - 1) : 1;
}

/**
 * Libreta de todas las matrículas de un aula en un año. $periodo: id del periodo, o null para todo el año.
 * Devuelve aula, año, periodos, cursos y estudiantes ordenados por mérito (con puesto).
 */
function libreta_aula($anio, $aula, $periodo = null) {
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("SELECT a.Id_aula AS id, n.Nivel_academico AS nivel, a.Grado AS grado, s.seccion_nombre AS seccion,
                             ae.`Id_año_escolar` AS id_anio, ae.`año_escolar` AS anio
                      FROM aulas a JOIN nivel_academico n ON n.Id_nivel = a.id_nivel_academico
                      LEFT JOIN seccion s ON s.seccion_id = a.id_seccion
                      JOIN `año_escolar` ae ON ae.`Id_año_escolar` = ?
                      WHERE a.Id_aula = ?");
    $q->execute([$anio, $aula]);
    $info = $q->fetch(PDO::FETCH_ASSOC);
    if (!$info) { return null; }
    $q = $c->prepare("SELECT id_periodo AS id, periodos AS nombre, fecha_inicio AS inicio, fecha_fin AS fin
                      FROM periodos WHERE `id_año_escolar` = ? ORDER BY fecha_inicio, id_periodo");
    $q->execute([$anio]);
    $todos = $q->fetchAll(PDO::FETCH_ASSOC);
    $periodos = $periodo ? array_values(array_filter($todos, function ($p) use ($periodo) { return (int) $p['id'] === (int) $periodo; })) : $todos;
    if ($periodo && !$periodos) { return null; }
    $ids_periodos = array_map(function ($p) { return (int) $p['id']; }, $periodos);

    $q = $c->prepare("SELECT m.id_matricula AS matricula, al.alum_dni AS documento,
                             CONCAT_WS(' ', al.alum_apepat, al.alum_apemat) AS apellidos, al.alum_nombre AS nombres
                      FROM matricula m JOIN alumnos al ON al.Id_alumno = m.id_alumno
                      WHERE m.id_aula = ? AND m.`id_año` = ?
                      ORDER BY al.alum_apepat, al.alum_apemat, al.alum_nombre");
    $q->execute([$aula, $anio]);
    $estudiantes = $q->fetchAll(PDO::FETCH_ASSOC);

    // Notas de criterios: [matricula][curso][periodo][] = nota; y el detalle por criterio para la libreta individual
    $q = $c->prepare("SELECT n.id_matricula, asg.Id_asignatura AS id_curso, asg.nombre_asig AS curso, n.id_bimestre AS periodo,
                             cr.competencias AS criterio, n.nota, n.conclusiones
                      FROM notas n JOIN matricula m ON m.id_matricula = n.id_matricula
                      JOIN criterios cr ON cr.id_criterio = n.id_criterio
                      JOIN detalle_asignatura_docente dd ON dd.Id_detalle_asig_docente = cr.id_detalle_asignatura
                      JOIN asignaturas asg ON asg.Id_asignatura = dd.Id_asignatura
                      WHERE m.id_aula = ? AND m.`id_año` = ?
                      ORDER BY asg.nombre_asig, cr.id_criterio");
    $q->execute([$aula, $anio]);
    $notas = [];
    $detalle = [];
    $cursos = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (!in_array((int) $r['periodo'], $ids_periodos, true)) { continue; }
        $cursos[$r['id_curso']] = $r['curso'];
        $notas[$r['id_matricula']][$r['id_curso']][$r['periodo']][] = $r['nota'];
        $detalle[$r['id_matricula']][$r['id_curso']][] = ['periodo' => (int) $r['periodo'], 'criterio' => $r['criterio'], 'nota' => $r['nota'], 'conclusion' => $r['conclusiones']];
    }
    // Cursos del aula aunque aún no tengan notas
    $q = $c->prepare("SELECT DISTINCT asg.Id_asignatura AS id, asg.nombre_asig AS curso FROM asignaturas asg
                      JOIN detalle_asignatura_docente dd ON dd.Id_asignatura = asg.Id_asignatura
                      JOIN asignatura_docente ad ON ad.Id_asigdocente = dd.Id_asig_docente
                      WHERE asg.Id_grado = ? AND ad.`id_año` = ?");
    $q->execute([$aula, $anio]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $cursos[$r['id']] = $r['curso']; }
    asort($cursos, SORT_NATURAL | SORT_FLAG_CASE);

    // Asistencia del rango
    $desde = $periodos ? min(array_column($periodos, 'inicio')) : null;
    $hasta = $periodos ? max(array_column($periodos, 'fin')) : null;
    $q = $c->prepare("SELECT x.id_matricula, SUM(x.estado = 'PRESENTE') AS P, SUM(x.estado = 'TARDE') AS T, SUM(x.estado = 'AUSENTE') AS F,
                             SUM(x.estado = 'JUSTIFICADO') AS J
                      FROM asistencia x JOIN matricula m ON m.id_matricula = x.id_matricula
                      WHERE m.id_aula = ? AND m.`id_año` = ? AND (? IS NULL OR x.fecha BETWEEN ? AND ?)
                      GROUP BY x.id_matricula");
    $q->execute([$aula, $anio, $desde, $desde, $hasta]);
    $asis = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { $asis[$r['id_matricula']] = array_map('intval', array_slice($r, 1, 4, true)); }

    foreach ($estudiantes as &$e) {
        $m = $e['matricula'];
        $e['cursos'] = [];
        $finales = [];
        foreach ($cursos as $id => $nombre) {
            $por_periodo = [];
            foreach ($ids_periodos as $p) {
                $por_periodo[$p] = isset($notas[$m][$id][$p]) ? libreta_promedio($notas[$m][$id][$p]) : null;
            }
            $final = libreta_promedio(array_values(array_filter($por_periodo, function ($x) { return $x !== null; })));
            $e['cursos'][$id] = ['periodos' => $por_periodo, 'final' => $final, 'aprobado' => $final === null ? null : nota_aprobada($final)];
            if ($final !== null) { $finales[] = $final; }
        }
        $e['detalle'] = $detalle[$m] ?? [];
        $e['promedio'] = $finales ? libreta_promedio($finales) : null;
        $fr = array_filter(array_map('libreta_fraccion', $finales), function ($x) { return $x !== null; });
        $e['puntaje'] = $fr ? array_sum($fr) / count($fr) : null;
        $e['desaprobados'] = count(array_filter($e['cursos'], function ($x) { return $x['aprobado'] === false; }));
        $e['con_notas'] = count($finales);
        $e['asistencia'] = $asis[$m] ?? ['P' => 0, 'T' => 0, 'F' => 0, 'J' => 0];
    }
    unset($e);

    // Orden de mérito: mayor puntaje primero; empates comparten puesto (1, 1, 3). Sin notas, al final y sin puesto.
    usort($estudiantes, function ($a, $b) {
        if ($a['puntaje'] === null || $b['puntaje'] === null) {
            return ($a['puntaje'] === null) <=> ($b['puntaje'] === null) ?: strcmp($a['apellidos'] . $a['nombres'], $b['apellidos'] . $b['nombres']);
        }
        return round($b['puntaje'], 6) <=> round($a['puntaje'], 6) ?: strcmp($a['apellidos'] . $a['nombres'], $b['apellidos'] . $b['nombres']);
    });
    $puesto = 0;
    $anterior = null;
    foreach ($estudiantes as $i => &$e) {
        if ($e['puntaje'] === null) { $e['puesto'] = null; continue; }
        if ($anterior === null || round($e['puntaje'], 6) !== round($anterior, 6)) { $puesto = $i + 1; }
        $e['puesto'] = $puesto;
        $anterior = $e['puntaje'];
    }
    unset($e);

    $lista_cursos = [];
    foreach ($cursos as $id => $nombre) { $lista_cursos[] = ['id' => $id, 'nombre' => $nombre]; }
    return ['aula' => $info, 'periodos' => $periodos, 'todo_el_anio' => !$periodo, 'cursos' => $lista_cursos,
            'estudiantes' => $estudiantes, 'escala' => descripcion_escala()];
}

/** Aula y año de una matrícula (para la libreta individual). */
function libreta_de_matricula($id_matricula) {
    $c = (new conexionBD())->conexionPDO();
    $q = $c->prepare("SELECT `id_año` AS anio, id_aula AS aula FROM matricula WHERE id_matricula = ?");
    $q->execute([$id_matricula]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
