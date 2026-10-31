<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Estado de resultados (Fase 7): ingresos − egresos por categoría = utilidad del período.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/validacion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
require_once __DIR__ . '/../../../model/model_reportes.php';

pdf_parametros(['desde', 'hasta']);
[$desde, $hasta] = rango_fechas_post();
$MR = new Modelo_Reportes();
$r = $MR->Resultados($desde, $hasta);

$filas = '';
foreach (['INGRESO' => 'INGRESOS', 'EGRESO' => 'EGRESOS'] as $tipo => $titulo) {
    $lado = $r[$tipo];
    $filas .= '<tr class="grupo"><td colspan="4">' . $titulo . '</td></tr>';
    foreach ($lado['grupos'] as $g) {
        $pct = $lado['total'] > 0 ? round($g['total'] / $lado['total'] * 100, 1) . ' %' : '';
        $filas .= '<tr class="grupo"><td>' . pdf_esc(trim($g['codigo'] . ' ' . $g['nombre'])) . '</td><td></td><td class="der">' . formato_moneda($g['total']) . '</td><td class="der">' . $pct . '</td></tr>';
        foreach ($g['categorias'] as $c) {
            $filas .= '<tr class="categoria"><td class="nombre">' . pdf_esc($c['nombre']) . '</td><td class="der">' . $c['cantidad'] . '</td><td class="der">'
                    . formato_moneda($c['total']) . '</td><td class="der">' . $c['porcentaje'] . ' %</td></tr>';
            foreach ($c['detalle'] as $d) {
                $filas .= '<tr class="detalle"><td class="nombre">' . pdf_esc($d['concepto']) . '</td><td class="der">' . $d['cantidad'] . '</td><td class="der">'
                        . formato_moneda($d['total']) . '</td><td></td></tr>';
            }
        }
    }
    if (!$lado['grupos']) {
        $filas .= '<tr><td colspan="4">Sin movimientos en el período</td></tr>';
    }
    $filas .= '<tr class="total"><td>TOTAL ' . $titulo . '</td><td></td><td class="der">' . formato_moneda($lado['total']) . '</td><td></td></tr>';
}
$ganancia = $r['utilidad'] >= 0;
$filas .= '<tr class="total"><td>' . ($ganancia ? 'UTILIDAD DEL PERÍODO' : 'PÉRDIDA DEL PERÍODO') . '</td><td></td><td class="der' . ($ganancia ? '' : ' rojo') . '">'
        . formato_moneda($r['utilidad']) . '</td><td class="der">' . ($r['margen'] === null ? '' : $r['margen'] . ' %') . '</td></tr>';

$html = pdf_encabezado($mysqli, 'ESTADO DE RESULTADOS', 'Del ' . pdf_fecha($desde) . ' al ' . pdf_fecha($hasta)) . '
<table>
  <tr><th>Concepto</th><th class="der">Operaciones</th><th class="der">Monto</th><th class="der">% del total</th></tr>
  ' . $filas . '
</table>
<p class="nota">Ingresos y egresos válidos con fecha dentro del período; los anulados no se incluyen. El margen es la utilidad sobre los ingresos.</p>
<br><br><br>
<table style="border:none"><tr><td style="border:none; text-align:center">______________________<br>Contador(a)</td>
<td style="border:none; text-align:center">______________________<br>Dirección</td></tr></table>';

$mpdf = pdf_nuevo('A4');
$mpdf->WriteHTML($html);
$mpdf->Output('estado_resultados_' . $desde . '_' . $hasta . '.pdf', 'I');
