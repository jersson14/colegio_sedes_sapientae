<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Flujo de caja (Fase 7): saldo inicial, entradas, salidas y saldo final de cada caja/cuenta.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/validacion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
require_once __DIR__ . '/../../../model/model_reportes.php';

pdf_parametros(['desde', 'hasta']);
[$desde, $hasta] = rango_fechas_post();
$MR = new Modelo_Reportes();
$r = $MR->Flujo($desde, $hasta);
$campos = ['saldo_anterior', 'cobros', 'transferencias_entrada', 'ajustes_entrada', 'pagos', 'transferencias_salida', 'ajustes_salida', 'saldo_final'];

$filas = '';
foreach ($r['data'] as $c) {
    $filas .= '<tr><td><b>' . pdf_esc($c['nombre']) . '</b><br>' . pdf_esc($c['tipo']) . '</td>';
    foreach ($campos as $k) {
        $filas .= '<td class="der' . ($k === 'saldo_final' && $c[$k] < 0 ? ' rojo' : '') . '">' . formato_moneda($c[$k]) . '</td>';
    }
    $filas .= '</tr>';
}
$pie = '<tr class="total"><td>TOTAL</td>';
foreach ($campos as $k) {
    $pie .= '<td class="der">' . formato_moneda($r['totales'][$k]) . '</td>';
}
$pie .= '</tr>';
$s = $r['sin_cuenta'];
$nota_sin_cuenta = $s ? '<p class="nota">Además, en el período hay ' . formato_moneda($s['cobros']) . ' de cobros y ' . formato_moneda($s['pagos'])
    . ' de pagos registrados sin caja/cuenta (anteriores al control de cajas o sin cuenta asignada); suman en la utilidad pero no en estos saldos.</p>' : '';

$html = pdf_encabezado($mysqli, 'FLUJO DE CAJA', 'Del ' . pdf_fecha($desde) . ' al ' . pdf_fecha($hasta)) . '
<table>
  <tr><th>Caja / cuenta</th><th class="der">Saldo inicial</th><th class="der">Cobros</th><th class="der">Transf. recibidas</th><th class="der">Ajustes (+)</th>
      <th class="der">Pagos</th><th class="der">Transf. enviadas</th><th class="der">Ajustes (−)</th><th class="der">Saldo final</th></tr>
  ' . ($filas ?: '<tr><td colspan="9">No hay cajas ni cuentas registradas</td></tr>') . $pie . '
</table>
<p class="nota">Cobros = recibos de pensiones/matrícula e ingresos; Pagos = egresos (incluida la planilla docente). Las transferencias mueven dinero entre cuentas y no cambian el total.</p>' . $nota_sin_cuenta;

$mpdf = pdf_nuevo('A4-L');
$mpdf->WriteHTML($html);
$mpdf->Output('flujo_caja_' . $desde . '_' . $hasta . '.pdf', 'I');
