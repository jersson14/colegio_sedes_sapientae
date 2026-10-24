<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Boleta de pago del docente (Fase 6): sueldo/horas, ingresos, descuentos, adelantos, neto y pagos.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$id = (int) ($_GET['id'] ?? 0);

$st = $mysqli->prepare("SELECT d.*, p.anio, p.mes, p.estado AS estado_planilla,
        ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
        CONCAT_WS(' ', o.docente_nombre, o.docente_apelli) AS docente, o.docente_dni, o.id_asusuario,
        c.banco_docente, c.cuenta_docente, mp.nombre AS metodo,
        e.emp_razon, e.emp_logo, e.emp_direccion, e.identificacion_tributaria, pa.id_tributario
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN docentes o ON o.Id_docente = d.docente_id
    INNER JOIN contratos_docente c ON c.id_contrato = d.contrato_id
    LEFT JOIN metodos_pago mp ON mp.id_metodo = c.metodo_pago_id
    CROSS JOIN (SELECT * FROM empresa ORDER BY empresa_id LIMIT 1) e
    LEFT JOIN paises pa ON pa.id_pais = e.pais_id
    WHERE d.id_detalle = ?");
$st->bind_param('i', $id);
$st->execute();
$r = $st->get_result()->fetch_assoc();
if (!$r) {
    http_response_code(404);
    exit('Boleta no encontrada');
}
// El docente solo ve sus boletas, y solo de planillas aprobadas o pagadas
if (rol_actual() === 'DOCENTE' && ((int) $r['id_asusuario'] !== (int) usuario_actual_id() || !in_array($r['estado_planilla'], ['APROBADA', 'PAGADA'], true))) {
    registrar_acceso_denegado('boleta_docente.php', 'BOLETA_AJENA');
    http_response_code(403);
    exit('No tiene permiso para ver esta boleta');
}
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

$st = $mysqli->prepare("SELECT k.nombre, k.tipo, k.forma, x.valor, x.monto FROM planilla_detalle_conceptos x
    INNER JOIN conceptos_planilla k ON k.id_concepto = x.concepto_id WHERE x.id_detalle = ? ORDER BY k.tipo DESC, k.nombre");
$st->bind_param('i', $id);
$st->execute();
$conceptos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st = $mysqli->prepare("SELECT p.tipo, DATE_FORMAT(p.fecha, '%d-%m-%Y') AS fecha, p.monto, mp.nombre AS metodo, p.referencia
    FROM pagos_docentes p INNER JOIN metodos_pago mp ON mp.id_metodo = p.metodo_pago_id
    WHERE p.estado = 'VALIDO' AND (p.id_detalle = ? OR p.id_detalle_descuento = ?) ORDER BY p.fecha");
$st->bind_param('ii', $id, $id);
$st->execute();
$pagos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$ingresos = $descuentos = '';
$base_txt = $r['calculo'] === 'POR_HORA' ? 'Horas dictadas: ' . $e($r['horas']) . ' x ' . formato_moneda($r['tarifa_hora']) : 'Sueldo mensual';
foreach ($conceptos as $c) {
    $fila = '<tr><td>' . $e($c['nombre']) . ($c['forma'] === 'PORCENTAJE' ? ' (' . $e($c['valor']) . '%)' : '') . '</td><td class="der">' . formato_moneda($c['monto']) . '</td></tr>';
    if ($c['tipo'] === 'INGRESO') { $ingresos .= $fila; } else { $descuentos .= $fila; }
}
if ($r['adelantos'] > 0) {
    $descuentos .= '<tr><td>Adelantos</td><td class="der">' . formato_moneda($r['adelantos']) . '</td></tr>';
}
$filas_pagos = '';
foreach ($pagos as $p) {
    $filas_pagos .= '<tr><td>' . $e($p['fecha']) . '</td><td>' . ($p['tipo'] === 'ADELANTO' ? 'Adelanto' : 'Pago') . '</td><td>' . $e($p['metodo'])
                  . '</td><td>' . $e($p['referencia']) . '</td><td class="der">' . formato_moneda($p['monto']) . '</td></tr>';
}
$tributario = $r['identificacion_tributaria'] ? ' · ' . $e($r['id_tributario']) . ': ' . $e($r['identificacion_tributaria']) : '';

$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
  th { background-color: #f2f2f2; }
  .der { text-align: right; }
  .titulo { font-size: 15px; font-weight: bold; color: #0A5D86; text-align: center; }
</style>
<table style="border:none"><tr>
  <td style="border:none; width:25%"><img src="../../../' . $e($r['emp_logo']) . '" style="max-width:110px"></td>
  <td style="border:none"><b>' . $e($r['emp_razon']) . '</b>' . $tributario . '<br>' . $e($r['emp_direccion']) . '</td>
</tr></table>
<p class="titulo">BOLETA DE PAGO – ' . $e($r['mes_nombre']) . ' ' . $e($r['anio']) . '</p>
<table>
  <tr><th>Docente</th><td>' . $e($r['docente']) . '</td><th>' . nombre_documento() . '</th><td>' . $e($r['docente_dni']) . '</td></tr>
  <tr><th>Contrato</th><td>' . $e($r['tipo_contrato']) . '</td><th>Forma de pago</th><td>' . $e($r['metodo'])
     . ($r['cuenta_docente'] ? ' · ' . $e($r['banco_docente']) . ' ' . $e($r['cuenta_docente']) : '') . '</td></tr>
</table>
<table>
  <tr><th colspan="2">INGRESOS</th></tr>
  <tr><td>' . $base_txt . '</td><td class="der">' . formato_moneda($r['base']) . '</td></tr>
  ' . $ingresos . '
  <tr><td><b>Total ingresos</b></td><td class="der"><b>' . formato_moneda($r['base'] + $r['ingresos']) . '</b></td></tr>
  <tr><th colspan="2">DESCUENTOS</th></tr>
  ' . ($descuentos ?: '<tr><td colspan="2">Sin descuentos</td></tr>') . '
  <tr><td><b>Total descuentos</b></td><td class="der"><b>' . formato_moneda($r['descuentos'] + $r['adelantos']) . '</b></td></tr>
  <tr><th>NETO A PAGAR</th><th class="der">' . formato_moneda($r['neto']) . '</th></tr>
</table>
<p style="margin-top:8px"><b>Pagos realizados</b></p>
<table>
  <tr><th>Fecha</th><th>Tipo</th><th>Forma de pago</th><th>Referencia</th><th class="der">Monto</th></tr>
  ' . ($filas_pagos ?: '<tr><td colspan="5">Aún sin pagos</td></tr>') . '
  <tr><td colspan="4" class="der"><b>Pendiente</b></td><td class="der"><b>' . formato_moneda($r['neto'] - $r['pagado']) . '</b></td></tr>
</table>
<br><br><br>
<table style="border:none"><tr>
  <td style="border:none; text-align:center">______________________<br>Empleador</td>
  <td style="border:none; text-align:center">______________________<br>' . $e($r['docente']) . '</td>
</tr></table>';

$mpdf = new \Mpdf\Mpdf(['mode' => 'UTF-8', 'format' => 'A4']);
if ($r['estado_planilla'] === 'BORRADOR') {
    $mpdf->SetWatermarkText('BORRADOR');
    $mpdf->showWatermarkText = true;
}
$mpdf->WriteHTML($html);
$mpdf->Output('boleta_' . $r['anio'] . '_' . $r['mes'] . '.pdf', 'I');
