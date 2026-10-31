<?php
require_once __DIR__ . '/../../../utilitario/seguridad_reporte.php';
// Constancia de un pago de pensión registrado sin recibo numerado (pagos antiguos). Los pagos nuevos usan recibo.php.
// ?codigo=ID_MATRICULA&idpagopen=ID_PAGO_PENSION   (el estudiante solo los de su matrícula)
// Identidad del colegio común a todos los PDF (Fase 13, paso 11).
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../conexion.php';
require_once __DIR__ . '/../../../utilitario/reporte_pdf.php';

$codigo = (int) ($_GET['codigo'] ?? 0);
$idpagopen = (int) ($_GET['idpagopen'] ?? 0);
reporte_exigir_matricula_propia($mysqli, $codigo);

$st = $mysqli->prepare("SELECT pp.concepto, pe.mes, YEAR(pe.fecha_vencimiento) AS anio, pp.fecha_pago, pp.sub_total,
        a.alum_dni, CONCAT_WS(' ', a.alum_nombre, a.alum_apepat, a.alum_apemat) AS estudiante,
        n.Nivel_academico AS nivel, au.Grado AS grado, s.seccion_nombre AS seccion
    FROM pago_pensiones pp
    LEFT JOIN pensiones pe ON pe.id_pensiones = pp.id_pension
    INNER JOIN matricula m ON m.id_matricula = pp.id_matri
    INNER JOIN alumnos a ON a.Id_alumno = m.id_alumno
    INNER JOIN aulas au ON au.Id_aula = m.id_aula
    INNER JOIN nivel_academico n ON n.Id_nivel = au.id_nivel_academico
    LEFT JOIN seccion s ON s.seccion_id = au.id_seccion
    WHERE pp.id_matri = ? AND pp.id_pago_pension = ? AND pp.estado = 'VALIDO'");
$st->bind_param('ii', $codigo, $idpagopen);
$st->execute();
$r = $st->get_result()->fetch_assoc();
if (!$r) {
    http_response_code(404);
    exit('Pago no encontrado');
}
$grado = pdf_titulo($r['grado']) . ($r['seccion'] && $r['seccion'] !== 'UNICO' ? ' "' . $r['seccion'] . '"' : '');
$detalle = $r['mes'] ? pdf_titulo($r['mes']) . ' ' . $r['anio'] : '';

$html = pdf_estilos(10) . '
<style>
  .tabla { margin-top: 8px; }
  .datos td { border: none; padding: 2px 0; }
  .gracias { text-align: center; margin-top: 14px; }
</style>
' . pdf_cabecera('CONSTANCIA DE PAGO', pdf_fecha($r['fecha_pago']), true) . '
<table class="datos">
  <tr><td><b>' . pdf_esc(nombre_documento()) . ':</b> ' . pdf_esc($r['alum_dni']) . '</td></tr>
  <tr><td><b>Estudiante:</b> ' . pdf_esc(pdf_titulo($r['estudiante'])) . '</td></tr>
  <tr><td><b>Nivel y grado:</b> ' . pdf_esc(pdf_titulo($r['nivel'])) . ' · ' . pdf_esc($grado) . '</td></tr>
</table>
<table class="tabla">
  <tr><th>Concepto</th><th>Detalle</th><th class="der">Monto</th></tr>
  <tr><td>' . pdf_esc(pdf_titulo($r['concepto'])) . '</td><td>' . pdf_esc($detalle) . '</td><td class="der"><b>' . formato_moneda($r['sub_total']) . '</b></td></tr>
</table>
<p class="gracias"><b>¡Gracias por su pago!</b></p>
<p class="nota" style="text-align:center">Constancia de un pago registrado en el sistema. Puede canjearla por el comprobante original en el colegio.</p>';

$mpdf = pdf_nuevo([130, 190]);
$mpdf->SetTitle('Constancia de pago');
$mpdf->WriteHTML($html);
$mpdf->Output('constancia_pago.pdf', 'I');
