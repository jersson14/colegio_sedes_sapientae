<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Boleta de pago del docente (Fase 6) o del personal administrativo (Fase 8B): sueldo/horas, ingresos, descuentos, adelantos, neto y pagos.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';
$id = (int) ($_GET['id'] ?? 0);

$st = $mysqli->prepare("SELECT d.*, p.anio, p.mes, p.estado AS estado_planilla,
        ELT(p.mes, 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE') AS mes_nombre,
        CONCAT_WS(' ', o.nombres, o.apellidos) AS docente, o.documento AS docente_dni, o.usuario_id AS id_asusuario, o.cargo, p.tipo_personal,
        c.banco_docente, c.cuenta_docente, mp.nombre AS metodo,
        e.emp_razon, e.emp_logo, e.emp_direccion, e.identificacion_tributaria, pa.id_tributario
    FROM planilla_detalle d
    INNER JOIN planillas p ON p.id_planilla = d.id_planilla
    INNER JOIN vw_personal_planilla o ON o.tipo = d.persona_tipo AND o.id = d.persona_id
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
// Fuera de administración y contabilidad, cada persona solo ve sus boletas, y solo de planillas aprobadas o pagadas
if (!in_array(rol_actual(), ['ADMINISTRADOR', 'CONTADOR'], true) && ((int) $r['id_asusuario'] !== (int) usuario_actual_id() || !in_array($r['estado_planilla'], ['APROBADA', 'PAGADA'], true))) {
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
$st = $mysqli->prepare("SELECT p.tipo, DATE_FORMAT(p.fecha, '%d/%m/%Y') AS fecha, p.monto, mp.nombre AS metodo, p.referencia
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
$html = pdf_estilos(10) . '
<style>
  table { margin-top: 6px; }
  th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
  th { background-color: ' . pdf_identidad()['claro'] . '; color: ' . pdf_identidad()['color'] . '; }
  .cab td, .firmas td { border: none; }
</style>
' . pdf_cabecera('BOLETA DE PAGO', pdf_titulo($r['mes_nombre']) . ' ' . $r['anio']) . '
<table>
  <tr><th>' . ($r['tipo_personal'] === 'DOCENTE' ? 'Docente' : 'Trabajador') . '</th><td>' . pdf_esc(pdf_titulo($r['docente'])) . ($r['tipo_personal'] === 'DOCENTE' ? '' : ' (' . pdf_esc(pdf_titulo($r['cargo'])) . ')') . '</td><th>' . nombre_documento() . '</th><td>' . $e($r['docente_dni']) . '</td></tr>
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
<table class="firmas"><tr>
  <td>______________________________<br>Empleador</td>
  <td>______________________________<br>' . pdf_esc(pdf_titulo($r['docente'])) . '</td>
</tr></table>';

$mpdf = pdf_nuevo('A4');
if ($r['estado_planilla'] === 'BORRADOR') {
    $mpdf->SetWatermarkText('BORRADOR');
    $mpdf->showWatermarkText = true;
}
$mpdf->WriteHTML($html);
$mpdf->Output('boleta_' . $r['anio'] . '_' . $r['mes'] . '.pdf', 'I');
