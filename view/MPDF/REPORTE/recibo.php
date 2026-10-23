<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Recibo de pago (Fase 4): número correlativo, conceptos, formas de pago y usuario que lo registró.
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
$id = (int) ($_GET['id'] ?? 0);

$st = $mysqli->prepare("SELECT p.*, CONCAT(p.serie, '-', LPAD(p.numero, 6, '0')) AS recibo,
        DATE_FORMAT(p.fecha, '%d-%m-%Y %H:%i') AS fecha_txt,
        a.alum_dni, CONCAT_WS(' ', a.alum_nombre, a.alum_apepat, a.alum_apemat) AS estudiante,
        n.Nivel_academico, CONCAT_WS(' - ', au.Grado, s.seccion_nombre) AS grado,
        e.emp_razon, e.emp_logo, e.emp_email, e.emp_telefono, e.emp_direccion, e.identificacion_tributaria,
        pa.nombre AS pais, pa.id_tributario, u.usu_usuario AS registrado_por
    FROM pagos p
    INNER JOIN matricula m ON m.id_matricula = p.id_matricula
    INNER JOIN alumnos a ON a.Id_alumno = m.id_alumno
    INNER JOIN aulas au ON au.Id_aula = m.id_aula
    INNER JOIN nivel_academico n ON n.Id_nivel = au.id_nivel_academico
    LEFT JOIN seccion s ON s.seccion_id = au.id_seccion
    LEFT JOIN usuario u ON u.usu_id = p.usuario_id
    CROSS JOIN (SELECT * FROM empresa ORDER BY empresa_id LIMIT 1) e
    LEFT JOIN paises pa ON pa.id_pais = e.pais_id
    WHERE p.id_pago = ?");
$st->bind_param('i', $id);
$st->execute();
$r = $st->get_result()->fetch_assoc();
if (!$r) {
    http_response_code(404);
    exit('Recibo no encontrado');
}
// Un estudiante solo puede ver los recibos de su matrícula
reporte_exigir_matricula_propia($mysqli, $r['id_matricula']);

$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

$st = $mysqli->prepare("SELECT pp.concepto, pe.mes, YEAR(pe.fecha_vencimiento) AS anio, pp.sub_total, pp.mora_exonerada, pp.motivo_edicion
    FROM pago_pensiones pp LEFT JOIN pensiones pe ON pe.id_pensiones = pp.id_pension
    WHERE pp.id_pago = ? ORDER BY pp.id_pago_pension");
$st->bind_param('i', $id);
$st->execute();
$conceptos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $mysqli->prepare("SELECT mp.nombre AS metodo, c.nombre AS cuenta, pm.referencia, pm.monto
    FROM pagos_metodos pm INNER JOIN metodos_pago mp ON mp.id_metodo = pm.metodo_pago_id
    LEFT JOIN cuentas_financieras c ON c.id_cuenta = pm.cuenta_id
    WHERE pm.id_pago = ? ORDER BY pm.id_pago_metodo");
$st->bind_param('i', $id);
$st->execute();
$metodos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$filas = '';
foreach ($conceptos as $c) {
    $detalle = $c['mes'] ? $c['mes'] . ' ' . $c['anio'] : '';
    if ($c['mora_exonerada']) {
        $detalle .= ' (exonerada)';
    }
    $filas .= '<tr><td>' . $e($c['concepto']) . '</td><td>' . $e($detalle) . '</td><td class="der">' . formato_moneda($c['sub_total']) . '</td></tr>';
}
$filas_metodos = '';
foreach ($metodos as $m) {
    $filas_metodos .= '<tr><td>' . $e($m['metodo']) . '</td><td>' . $e($m['cuenta']) . '</td><td>' . $e($m['referencia'])
                    . '</td><td class="der">' . formato_moneda($m['monto']) . '</td></tr>';
}
$tributario = $r['identificacion_tributaria'] ? '<br>' . $e($r['id_tributario']) . ': ' . $e($r['identificacion_tributaria']) : '';

$html = '
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; }
  .header { text-align: center; }
  .logo { max-width: 120px; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
  th { background-color: #f2f2f2; }
  .der { text-align: right; }
  .numero { font-size: 15px; font-weight: bold; border: 2px solid #0A5D86; padding: 6px; text-align: center; color: #0A5D86; }
</style>
<div class="header">
  <img src="../../../' . $e($r['emp_logo']) . '" class="logo"><br>
  <b>' . $e($r['emp_razon']) . '</b>' . $tributario . '<br>' . $e($r['emp_direccion']) . '<br>' . $e($r['emp_telefono']) . ' · ' . $e($r['emp_email']) . '
</div>
<p class="numero">RECIBO DE PAGO N.° ' . $e($r['recibo']) . '</p>
<p><b>Fecha:</b> ' . $e($r['fecha_txt']) . '<br>
<b>' . nombre_documento() . ':</b> ' . $e($r['alum_dni']) . '<br>
<b>Estudiante:</b> ' . $e($r['estudiante']) . '<br>
<b>Nivel / Grado:</b> ' . $e($r['Nivel_academico']) . ' · ' . $e($r['grado']) . '</p>
<table>
  <tr><th>Concepto</th><th>Detalle</th><th class="der">Monto</th></tr>
  ' . $filas . '
  <tr><td colspan="2" class="der"><b>TOTAL</b></td><td class="der"><b>' . formato_moneda($r['total']) . '</b></td></tr>
</table>
<p style="margin-top:8px"><b>Forma de pago</b></p>
<table>
  <tr><th>Método</th><th>Caja / cuenta</th><th>Referencia</th><th class="der">Monto</th></tr>
  ' . ($filas_metodos ?: '<tr><td colspan="4">Sin monto a pagar</td></tr>') . '
</table>
' . ($r['observacion'] ? '<p><b>Observación:</b> ' . $e($r['observacion']) . '</p>' : '') . '
<p><b>Registrado por:</b> ' . $e($r['registrado_por'] ?: '-') . '</p>
' . ($r['estado'] === 'ANULADO' ? '<p style="color:#c00"><b>ANULADO</b> el ' . $e($r['fecha_anulacion']) . ': ' . $e($r['motivo_anulacion']) . '</p>' : '') . '
<p style="text-align:center; margin-top:14px"><b>¡Gracias por su pago!</b><br>' . $e($r['pais']) . '</p>';

$mpdf = new \Mpdf\Mpdf(['mode' => 'UTF-8', 'format' => [80, 200], 'margin_left' => 4, 'margin_right' => 4, 'margin_top' => 4]);
if ($r['estado'] === 'ANULADO') {
    $mpdf->SetWatermarkText('ANULADO');
    $mpdf->showWatermarkText = true;
}
$mpdf->WriteHTML($html);
$mpdf->Output('recibo_' . $r['recibo'] . '.pdf', 'I');
