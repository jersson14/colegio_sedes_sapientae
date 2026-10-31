<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Planilla mensual de asistencia (Fase 11), con el formato de la hoja de asistencia en papel.
// ?vacia=1 imprime la planilla sin marcas para llenarla a mano.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/validacion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
require_once __DIR__ . '/../../../utilitario/planilla_asistencia.php';

pdf_parametros(['anio', 'aula', 'mes']);
$datos = planilla_datos(entero_post('anio', 1, 'año escolar'), entero_post('aula', 1, 'aula'), $_POST['mes']);
if (!$datos['aula']) {
    exit('No hay aulas con estudiantes');
}
$vacia = !empty($_GET['vacia']);
$aula = $datos['aula'];
$grado = $aula['nivel'] . ' - ' . $aula['grado'] . ($aula['seccion'] && $aula['seccion'] !== 'UNICO' ? ' "' . $aula['seccion'] . '"' : '');
$colores = [1 => '#d9d4f5', '#c9eeee', '#fdeeb5', '#f5d2ee', '#d4efd2', '#e6e6e6'];
$fondo = ['P' => '#c8f0cf', 'T' => '#ffe6a6', 'F' => '#f8c9c9', 'J' => '#cfe2ff'];

$semanas = [];
foreach ($datos['dias'] as $d) {
    $semanas[$d['semana']] = ($semanas[$d['semana']] ?? 0) + 1;
}
$fila_sem = '';
foreach ($semanas as $s => $n) {
    $fila_sem .= '<td class="h" colspan="' . $n . '" style="background-color:' . $colores[$s] . '">' . ($n < 3 ? 'SEM. ' : 'SEMANA ') . $s . '</td>';
}
$fila_dias = '';
foreach ($datos['dias'] as $d) {
    $fila_dias .= '<td class="h dia" style="background-color:' . $colores[$d['semana']] . '">' . $d['letra'] . '<br>' . sprintf('%02d', $d['dia']) . '</td>';
}

$filas = '';
$n = 0;
foreach ($datos['estudiantes'] as $e) {
    $n++;
    $celdas = '';
    foreach ($datos['dias'] as $d) {
        $c = $vacia ? '' : ($e['marcas']->{$d['dia']} ?? '');
        $celdas .= '<td class="c"' . ($c ? ' style="background-color:' . $fondo[$c] . '"' : '') . '>' . $c . '</td>';
    }
    $t = $vacia ? ['P' => '', 'T' => '', 'F' => '', 'J' => '', 'porcentaje' => ''] : planilla_totales($e['marcas']);
    $filas .= '<tr><td class="c">' . $n . '</td><td class="nombre">' . pdf_esc(pdf_titulo($e['apellidos'] . ', ' . $e['nombres'])) . '</td>' . $celdas
        . '<td class="c">' . $t['P'] . '</td><td class="c">' . $t['T'] . '</td><td class="c">' . $t['F'] . '</td><td class="c">' . $t['J'] . '</td><td class="c">'
        . ($t['porcentaje'] === null || $t['porcentaje'] === '' ? '' : $t['porcentaje'] . '%') . '</td></tr>';
}
// Filas libres al final (como en la hoja impresa) para estudiantes que se incorporan
$libres = $vacia ? max(5, 30 - $n) : 3;
for ($i = 1; $i <= $libres; $i++) {
    $filas .= '<tr><td class="c">' . ($n + $i) . '</td><td class="nombre">&nbsp;</td>' . str_repeat('<td class="c"></td>', count($datos['dias']) + 5) . '</tr>';
}

$id = pdf_identidad();
$docentes = $datos['docentes'] ? implode(', ', $datos['docentes']) : '';

$html = pdf_estilos(8.5) . '
<style>
  .datos td { padding: 2px 4px; font-size: 9px; }
  .etq { background-color: ' . $id['claro'] . '; color: ' . $id['color'] . '; font-weight: bold; border: 1px solid #bbb; }
  .val { border: 1px solid #bbb; font-weight: bold; }
  .planilla { width: 100%; margin-top: 6px; }
  .planilla th, .planilla td { border: 1px solid #777; padding: 2px 1px; }
  .planilla .h { font-size: 8px; font-weight: bold; text-align: center; background-color: #f1f1f1; }
  .dia { width: 20px; }
  .planilla td.c { height: 15px; }
  .c { text-align: center; }
  .nombre { padding-left: 4px; white-space: nowrap; }
  .planilla .mes { color: ' . $id['color'] . '; font-size: 12px; background-color: #fff; }
  .leyenda span { padding: 1px 5px; border: 1px solid #999; font-weight: bold; }
</style>
' . pdf_cabecera('LISTA DE ASISTENCIA', pdf_titulo($datos['nombre_mes']) . ' · Año escolar ' . $datos['anio']['anio']) . '
<table class="datos" style="width:100%"><tr>
  <td class="etq" style="width:13%">Grado y sección</td><td class="val" style="width:30%">' . pdf_esc(pdf_titulo($grado)) . '</td>
  <td class="etq" style="width:12%">Docente(s)</td><td class="val">' . pdf_esc(pdf_titulo($docentes)) . '</td>
  <td class="etq" style="width:7%">Fecha</td><td class="val" style="width:10%">' . date('d/m/Y') . '</td>
</tr></table>
<table class="planilla">
  <tr><td colspan="2" class="h mes">' . pdf_esc($datos['nombre_mes']) . '</td>' . $fila_sem . '<td class="h" colspan="5">RESUMEN</td></tr>
  <tr><td class="h" style="width:18px">Nº</td><td class="h" style="width:190px">APELLIDOS Y NOMBRES</td>' . $fila_dias
    . '<td class="h dia" style="background-color:' . $fondo['P'] . '">P</td><td class="h dia" style="background-color:' . $fondo['T'] . '">T</td>'
    . '<td class="h dia" style="background-color:' . $fondo['F'] . '">F</td><td class="h dia" style="background-color:' . $fondo['J'] . '">J</td><td class="h" style="width:26px">%</td></tr>
  ' . $filas . '
</table>
<p class="leyenda" style="margin-top:6px">Leyenda: <span style="background-color:' . $fondo['P'] . '">P</span> Presente &nbsp;
  <span style="background-color:' . $fondo['T'] . '">T</span> Tardanza &nbsp; <span style="background-color:' . $fondo['F'] . '">F</span> Falta &nbsp;
  <span style="background-color:' . $fondo['J'] . '">J</span> Falta justificada &nbsp;·&nbsp; % = (P + T + J) / días registrados</p>
<table style="width:100%; margin-top:30px"><tr>
  <td style="width:35%; text-align:center; border-top:1px solid #333">Firma del docente / tutor</td><td></td>
  <td style="width:35%; text-align:center; border-top:1px solid #333">Dirección</td>
</tr></table>';

$mpdf = pdf_nuevo('A4-L', ['margin_left' => 8, 'margin_right' => 8, 'margin_top' => 8, 'margin_bottom' => 8]);
$mpdf->shrink_tables_to_fit = 1;
$mpdf->WriteHTML($html);
$mpdf->Output('asistencia_' . $datos['mes'] . '.pdf', 'I');
