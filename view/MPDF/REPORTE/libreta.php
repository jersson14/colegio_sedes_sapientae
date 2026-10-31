<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Libreta de notas (Fase 13, paso 9) con la identidad del colegio (Configuración → Apariencia).
//   ?matricula=ID[&periodo=ID]          libreta de un estudiante (el estudiante solo la suya)
//   ?anio=ID&aula=ID[&periodo=ID]        libretas de toda el aula, una por hoja (solo dirección)
//   ?anio=ID&aula=ID[&periodo=ID]&merito=1   orden de mérito del aula (solo dirección)
// Sin periodo: todo el año (una columna por periodo y la nota final).
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/validacion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
require_once __DIR__ . '/../../../utilitario/libreta.php';
require_once __DIR__ . '/../../../utilitario/tema.php';

pdf_parametros(['matricula', 'anio', 'aula', 'periodo']);
$periodo = $_POST['periodo'] === '' ? null : entero_post('periodo', 1, 'periodo');
$solo = null;
if ($_POST['matricula'] !== '') {
    $solo = entero_post('matricula', 1, 'matrícula');
    reporte_exigir_matricula_propia($mysqli, $solo);
    $m = libreta_de_matricula($solo);
    if (!$m) { http_response_code(404); exit('La matrícula no existe'); }
    $anio = (int) $m['anio'];
    $aula = (int) $m['aula'];
} else {
    if (rol_actual() !== 'ADMINISTRADOR') {
        registrar_acceso_denegado('view/MPDF/REPORTE/libreta.php', 'AULA_COMPLETA');
        http_response_code(403);
        exit('No tiene permiso para ver este reporte');
    }
    $anio = entero_post('anio', 1, 'año escolar');
    $aula = entero_post('aula', 1, 'aula');
}
$L = libreta_aula($anio, $aula, $periodo);
if (!$L) { http_response_code(404); exit('No se encontró la libreta'); }

$TEMA = tema_actual();
$color = $TEMA['primario'] ?? '#7A1F2B';
$emp = $mysqli->query("SELECT emp_cod, emp_direccion, emp_telefono FROM empresa ORDER BY empresa_id LIMIT 1")->fetch_assoc() ?: [];
$titulo = function ($t) {
    $t = mb_convert_case(mb_strtolower(html_entity_decode((string) $t, ENT_QUOTES, 'UTF-8'), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $t = preg_replace_callback('/ (De|Del|La|Las|Los|El|Y|E|En|Con|Por|Para)\b/u', function ($m) { return ' ' . mb_strtolower($m[1], 'UTF-8'); }, $t);
    // números romanos de los periodos: "Ii Bimestre" -> "II Bimestre"
    return preg_replace_callback('/\b(i{1,3}|iv|vi{0,3}|ix|x)\b/iu', function ($m) { return mb_strtoupper($m[1], 'UTF-8'); }, $t);
};
$a = $L['aula'];
$grado = $titulo($a['nivel']) . ' · ' . $titulo($a['grado']) . ($a['seccion'] && $a['seccion'] !== 'UNICO' ? ' "' . $a['seccion'] . '"' : '');
$nombre_periodo = $L['todo_el_anio'] ? 'Año escolar ' . $a['anio'] : $titulo($L['periodos'][0]['nombre']) . ' · ' . $a['anio'];
$con_puesto = count(array_filter($L['estudiantes'], function ($e) { return $e['puesto'] !== null; }));
$logo = $TEMA['logo'] ? '<img src="../../../' . pdf_esc($TEMA['logo']) . '" style="max-height:52px; max-width:120px">' : '';

$estilos = '<style>
  body { font-family: Arial, sans-serif; font-size: 10px; color: #222; }
  table { width: 100%; border-collapse: collapse; }
  .cab td { border: none; vertical-align: middle; }
  .colegio { font-size: 15px; font-weight: bold; color: ' . $color . '; }
  .lema { color: #666; font-style: italic; font-size: 9px; }
  .doc { font-size: 13px; font-weight: bold; text-align: right; color: ' . $color . '; }
  .linea { border-bottom: 2px solid ' . $color . '; margin: 6px 0 10px; }
  .datos td { border: 1px solid #ddd; padding: 5px 6px; }
  .datos .e { background: #f4f2ee; font-weight: bold; width: 18%; }
  .notas th { background: ' . $color . '; color: #fff; padding: 5px; border: 1px solid ' . $color . '; font-size: 9px; }
  .notas td { border: 1px solid #ccc; padding: 5px; }
  .c { text-align: center; }
  .mal { color: #B23A2A; font-weight: bold; }
  .crit td { color: #555; font-size: 9px; background: #fafafa; }
  .crit td.n { padding-left: 16px; }
  .res td { border: 1px solid #ccc; padding: 6px; }
  .res .e { background: #f4f2ee; font-weight: bold; }
  .nota { color: #666; font-size: 8.5px; margin-top: 6px; }
  .firmas td { border: none; text-align: center; padding: 50px 6px 0; font-size: 9px; width: 33%; color: #333; }
</style>';

$cabecera = function ($doc) use ($logo, $TEMA, $emp) {
    return '<table class="cab"><tr><td style="width:130px">' . $logo . '</td><td><div class="colegio">' . pdf_esc($TEMA['nombre']) . '</div>'
        . ($TEMA['lema'] ? '<div class="lema">' . pdf_esc($TEMA['lema']) . '</div>' : '')
        . '<div style="color:#555; font-size:8.5px">' . pdf_esc(trim(($emp['emp_direccion'] ?? '') . (!empty($emp['emp_telefono']) ? ' · Tel. ' . $emp['emp_telefono'] : '')))
        . (!empty($emp['emp_cod']) ? ' · Código ' . pdf_esc($emp['emp_cod']) : '') . '</div></td>'
        . '<td class="doc">' . $doc . '</td></tr></table><div class="linea"></div>';
};

$libreta = function ($e) use ($L, $grado, $nombre_periodo, $con_puesto, $titulo, $cabecera) {
    $h = $cabecera('LIBRETA DE NOTAS<br><span style="font-size:10px; color:#444">' . pdf_esc($nombre_periodo) . '</span>');
    $puesto = $e['puesto'] !== null ? $e['puesto'] . '.º de ' . $con_puesto : '—';
    $h .= '<table class="datos"><tr><td class="e">Estudiante</td><td colspan="3"><b>' . pdf_esc($titulo($e['nombres'] . ' ' . $e['apellidos'])) . '</b></td></tr>'
        . '<tr><td class="e">' . pdf_esc(nombre_documento()) . '</td><td>' . pdf_esc($e['documento']) . '</td><td class="e">Grado y sección</td><td>' . pdf_esc($grado) . '</td></tr></table><br>';
    // Notas
    $h .= '<table class="notas"><tr><th style="text-align:left">Curso</th>';
    if ($L['todo_el_anio']) {
        foreach ($L['periodos'] as $p) { $h .= '<th>' . pdf_esc($titulo($p['nombre'])) . '</th>'; }
        $h .= '<th>Nota final</th>';
    } else {
        $h .= '<th style="width:12%">Nota</th><th style="width:16%">Estado</th>';
    }
    $h .= '</tr>';
    foreach ($L['cursos'] as $c) {
        $cn = $e['cursos'][$c['id']];
        $h .= '<tr><td><b>' . pdf_esc($titulo($c['nombre'])) . '</b></td>';
        if ($L['todo_el_anio']) {
            foreach ($L['periodos'] as $p) {
                $v = $cn['periodos'][(int) $p['id']] ?? null;
                $h .= '<td class="c' . ($v !== null && !nota_aprobada($v) ? ' mal' : '') . '">' . ($v ?? '—') . '</td>';
            }
            $h .= '<td class="c' . ($cn['aprobado'] === false ? ' mal' : '') . '"><b>' . ($cn['final'] ?? '—') . '</b></td></tr>';
        } else {
            $h .= '<td class="c' . ($cn['aprobado'] === false ? ' mal' : '') . '"><b>' . ($cn['final'] ?? '—') . '</b></td><td class="c">'
                . ($cn['aprobado'] === null ? 'Sin notas' : ($cn['aprobado'] ? 'Aprobado' : '<span class="mal">Desaprobado</span>')) . '</td></tr>';
            // En un periodo: el detalle de cada criterio con su conclusión
            foreach ($e['detalle'][$c['id']] ?? [] as $d) {
                $h .= '<tr class="crit"><td class="n">' . pdf_esc(html_entity_decode((string) $d['criterio'], ENT_QUOTES, 'UTF-8'))
                    . ($d['conclusion'] ? '<br><i>' . pdf_esc(html_entity_decode((string) $d['conclusion'], ENT_QUOTES, 'UTF-8')) . '</i>' : '')
                    . '</td><td class="c">' . pdf_esc($d['nota']) . '</td><td></td></tr>';
            }
        }
    }
    $h .= '</table><br>';
    // Resumen
    $as = $e['asistencia'];
    $h .= '<table class="res"><tr><td class="e" style="width:25%">Promedio general</td><td class="c" style="width:12%"><b>' . ($e['promedio'] ?? '—') . '</b></td>'
        . '<td class="e" style="width:25%">Orden de mérito</td><td class="c"><b>' . $puesto . '</b></td></tr>'
        . '<tr><td class="e">Cursos desaprobados</td><td class="c' . ($e['desaprobados'] ? ' mal' : '') . '">' . $e['desaprobados'] . '</td>'
        . '<td class="e">Asistencia</td><td class="c">' . $as['P'] . ' presentes · ' . $as['T'] . ' tardanzas · ' . $as['F'] . ' faltas · ' . $as['J'] . ' justificadas</td></tr></table>';
    $h .= '<p class="nota">Escala de calificación: ' . pdf_esc($L['escala']) . '. La nota de cada curso es el promedio de sus criterios'
        . ($L['todo_el_anio'] ? '; la nota final, el promedio de los periodos con notas.' : '.') . ' Emitida el ' . date('d/m/Y') . '.</p>';
    $h .= '<table class="firmas"><tr><td>______________________________<br>Director(a)</td><td>______________________________<br>Tutor(a)</td><td>______________________________<br>Padre, madre o apoderado</td></tr></table>';
    return $h;
};

$html = $estilos;
if (!empty($_GET['merito']) && !$solo) {
    // Orden de mérito del aula
    $html .= $cabecera('ORDEN DE MÉRITO<br><span style="font-size:10px; color:#444">' . pdf_esc($grado) . ' · ' . pdf_esc($nombre_periodo) . '</span>');
    $html .= '<table class="notas"><tr><th style="width:8%">Puesto</th><th style="text-align:left">Estudiante</th><th style="width:16%">' . pdf_esc(nombre_documento()) . '</th>'
        . '<th style="width:12%">Promedio</th><th style="width:14%">Desaprobados</th></tr>';
    foreach ($L['estudiantes'] as $e) {
        $html .= '<tr><td class="c"><b>' . ($e['puesto'] ?? '—') . '</b></td><td>' . pdf_esc($titulo($e['apellidos'] . ', ' . $e['nombres'])) . '</td><td class="c">' . pdf_esc($e['documento'])
            . '</td><td class="c"><b>' . ($e['promedio'] ?? '—') . '</b></td><td class="c' . ($e['desaprobados'] ? ' mal' : '') . '">' . $e['desaprobados'] . '</td></tr>';
    }
    $html .= '</table><p class="nota">Ordenado por el promedio de las notas de los cursos. Quienes aún no tienen notas van al final, sin puesto. Escala: ' . pdf_esc($L['escala']) . '.</p>';
    $archivo = 'orden_merito.pdf';
} else {
    $lista = $solo ? array_values(array_filter($L['estudiantes'], function ($e) use ($solo) { return (int) $e['matricula'] === $solo; })) : $L['estudiantes'];
    if (!$lista) { http_response_code(404); exit('No hay estudiantes'); }
    // En la libreta del aula el orden es alfabético (para repartirlas); el puesto va dentro de cada una
    usort($lista, function ($x, $y) { return strcmp($x['apellidos'] . $x['nombres'], $y['apellidos'] . $y['nombres']); });
    foreach ($lista as $i => $e) {
        $html .= ($i ? '<pagebreak />' : '') . $libreta($e);
    }
    $archivo = $solo ? 'libreta_' . $lista[0]['documento'] . '.pdf' : 'libretas_aula.pdf';
}

$mpdf = new \Mpdf\Mpdf(['mode' => 'UTF-8', 'format' => 'A4', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 10, 'margin_bottom' => 10]);
$mpdf->SetTitle('Libreta de notas');
$mpdf->WriteHTML($html);
$mpdf->Output($archivo, 'I');
