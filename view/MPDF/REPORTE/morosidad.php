<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Morosidad (Fase 7): alumnos con pensiones vencidas y su deuda, agrupados por grado, para la cobranza.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/validacion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
require_once __DIR__ . '/../../../model/model_reportes.php';

pdf_parametros(['anio', 'nivel', 'aula']);
$anio = entero_post('anio', 1, 'año escolar');
$MR = new Modelo_Reportes();
$r = $MR->Morosidad($anio, entero_post('nivel', 0, 'nivel'), entero_post('aula', 0, 'aula'));

usort($r['alumnos'], function ($x, $y) {
    return [$x['nivel'], $x['grado'], $x['seccion'], $x['alumno']] <=> [$y['nivel'], $y['grado'], $y['seccion'], $y['alumno']];
});
$filas = '';
$grupo = null;
foreach ($r['alumnos'] as $a) {
    $aula = $a['nivel'] . ' › ' . $a['grado'] . ($a['seccion'] && $a['seccion'] !== 'UNICO' ? ' ' . $a['seccion'] : '');
    if ($aula !== $grupo) {
        $filas .= '<tr class="grupo"><td colspan="7">' . pdf_esc($aula) . '</td></tr>';
        $grupo = $aula;
    }
    $filas .= '<tr><td>' . pdf_esc($a['alumno']) . '<br><span class="nota">' . pdf_esc(nombre_documento()) . ' ' . pdf_esc($a['documento']) . '</span></td><td>'
            . pdf_esc($a['telefonos']) . '</td><td>' . pdf_esc($a['meses']) . '</td><td class="der">' . $a['dias_atraso'] . '</td><td class="der">'
            . formato_moneda($a['pension']) . '</td><td class="der">' . formato_moneda($a['mora']) . '</td><td class="der"><b>' . formato_moneda($a['deuda']) . '</b></td></tr>';
}
$t = $r['totales'];
$aviso = $r['avisos'] ? '<p class="nota rojo">Atención: ' . count($r['avisos']) . ' pensiones tienen un vencimiento que no corresponde al año escolar; los días de atraso se cuentan desde esa fecha.</p>' : '';

$html = pdf_encabezado($mysqli, 'REPORTE DE MOROSIDAD', 'Año escolar ' . $MR->Nombre_Anio($anio) . ' · al ' . date('d-m-Y')) . $aviso . '
<table>
  <tr><th>Alumno</th><th>Teléfonos</th><th>Meses vencidos</th><th class="der">Máx. días</th><th class="der">Pensión</th><th class="der">Mora</th><th class="der">Deuda</th></tr>
  ' . ($filas ?: '<tr><td colspan="7">No hay alumnos con pensiones vencidas</td></tr>') . '
  <tr class="total"><td colspan="4">TOTAL · ' . $t['alumnos'] . ' alumno(s)</td><td class="der">' . formato_moneda($t['pension']) . '</td><td class="der">'
  . formato_moneda($t['mora']) . '</td><td class="der">' . formato_moneda($t['deuda']) . '</td></tr>
</table>
<p class="nota">Pensiones no pagadas completas hasta su vencimiento con saldo pendiente a la fecha de emisión.</p>';

$mpdf = new \Mpdf\Mpdf(['mode' => 'UTF-8', 'format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('morosidad_' . date('Ymd') . '.pdf', 'I');
