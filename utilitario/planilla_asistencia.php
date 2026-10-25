<?php
/**
 * Planilla mensual de asistencia (Fase 11): arma los datos que usan la pantalla, el guardado y el PDF.
 * Requiere la sesión iniciada (controller/_seguridad.php o utilitario/seguridad_reporte.php).
 */
require_once __DIR__ . '/../model/model_registro_asistencia.php';

const LETRAS_DIA = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
const MESES = [1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

/** El docente solo trabaja con sus aulas; administrador y auxiliar con todas. */
function planilla_usuario_docente() {
    return rol_actual() === 'DOCENTE' ? usuario_actual_id() : null;
}

/** Días de clase (lunes a viernes) del mes con su semana; $hoy marca hasta qué día se puede registrar. */
function planilla_dias($mes, $anio_escolar) {
    $inicio = new DateTime($mes . '-01');
    $fin = (clone $inicio)->modify('last day of this month');
    $hoy = date('Y-m-d');
    $dias = [];
    $semana = 0;
    for ($d = clone $inicio; $d <= $fin; $d->modify('+1 day')) {
        $dow = (int) $d->format('N');
        if ($dow > 5) {
            continue;
        }
        if ($dow === 1 || !$dias) {
            $semana++;
        }
        $f = $d->format('Y-m-d');
        $en_anio = (!$anio_escolar['fecha_inicio'] || $f >= $anio_escolar['fecha_inicio'])
                && (!$anio_escolar['fecha_fin'] || $f <= $anio_escolar['fecha_fin']);
        $dias[] = [
            'dia' => (int) $d->format('j'),
            'fecha' => $f,
            'letra' => LETRAS_DIA[$dow],
            'semana' => $semana,
            'hoy' => $f === $hoy,
            // no se registra a futuro; fuera del año escolar se avisa pero se permite (el año puede estar mal configurado)
            'habilitado' => $f <= $hoy,
            'en_anio' => $en_anio,
        ];
    }
    return $dias;
}

/**
 * Datos completos de la planilla. $anio y $aula pueden venir en 0: se elige el año activo y la primera aula.
 * Devuelve null en 'aula' si el usuario no tiene aulas.
 */
function planilla_datos($anio, $aula, $mes) {
    $M = new Modelo_Registro_Asistencia();
    $anios = $M->Anios();
    if (!$anio) {
        foreach ($anios as $a) {
            if ($a['estado'] === 'ACTIVO') { $anio = (int) $a['id']; break; }
        }
        $anio = $anio ?: (int) ($anios[0]['id'] ?? 0);
    }
    $anio_escolar = $M->Anio($anio);
    if (!$anio_escolar) {
        denegar(400, 'Año escolar no encontrado');
    }
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $mes)) {
        $mes = date('Y-m');
    }
    $docente = planilla_usuario_docente();
    $aulas = $M->Aulas($anio, $docente);
    $aula_sel = null;
    foreach ($aulas as $a) {
        if ((int) $a['id'] === (int) $aula) { $aula_sel = $a; }
    }
    if ($aula && !$aula_sel) {
        registrar_acceso_denegado('registro_asistencia', 'AULA_AJENA');
        denegar(403, 'No tiene asignada esa aula');
    }
    $aula_sel = $aula_sel ?: ($aulas[0] ?? null);

    $dias = planilla_dias($mes, $anio_escolar);
    $estudiantes = [];
    if ($aula_sel) {
        $marcas = $M->Marcas($anio, $aula_sel['id'], $mes . '-01', date('Y-m-t', strtotime($mes . '-01')));
        foreach ($M->Estudiantes($anio, $aula_sel['id']) as $e) {
            $e['marcas'] = (object) ($marcas[$e['matricula']] ?? []);
            $estudiantes[] = $e;
        }
    }
    [$y, $m] = explode('-', $mes);
    return [
        'anios' => $anios,
        'anio' => $anio_escolar,
        'aulas' => $aulas,
        'aula' => $aula_sel,
        'mes' => $mes,
        'nombre_mes' => MESES[(int) $m] . ' ' . $y,
        'dias' => $dias,
        'estudiantes' => $estudiantes,
        'docentes' => $aula_sel ? $M->Docentes_Aula($anio, $aula_sel['id']) : [],
    ];
}

/** Totales de un estudiante: cantidad por código y % de asistencia (presente, tardanza y justificada cuentan). */
function planilla_totales($marcas) {
    $t = ['P' => 0, 'T' => 0, 'F' => 0, 'J' => 0];
    foreach ((array) $marcas as $c) {
        if (isset($t[$c])) { $t[$c]++; }
    }
    $total = array_sum($t);
    $t['porcentaje'] = $total ? round(($t['P'] + $t['T'] + $t['J']) * 100 / $total) : null;
    return $t;
}
