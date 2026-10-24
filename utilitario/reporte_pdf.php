<?php
/**
 * Piezas comunes de los reportes financieros en PDF (Fase 7): estilos y encabezado con los datos del colegio.
 * Se usa desde view/MPDF/REPORTE/*.php (las rutas de imagen son relativas a esa carpeta).
 */
require_once __DIR__ . '/regional.php';

function pdf_esc($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function pdf_fecha($f) {
    return $f ? date('d-m-Y', strtotime($f)) : '';
}

function pdf_encabezado($mysqli, $titulo, $subtitulo = '') {
    $e = $mysqli->query("SELECT e.emp_razon, e.emp_logo, e.emp_direccion, e.identificacion_tributaria, pa.id_tributario
                           FROM empresa e LEFT JOIN paises pa ON pa.id_pais = e.pais_id ORDER BY e.empresa_id LIMIT 1")->fetch_assoc() ?: [];
    $trib = !empty($e['identificacion_tributaria']) ? ' · ' . pdf_esc($e['id_tributario']) . ': ' . pdf_esc($e['identificacion_tributaria']) : '';
    $logo = !empty($e['emp_logo']) ? '<img src="../../../' . pdf_esc($e['emp_logo']) . '" style="max-width:100px">' : '';
    return '
<style>
  body { font-family: Arial, sans-serif; font-size: 10px; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
  th { background-color: #0A5D86; color: #fff; }
  .der { text-align: right; }
  .grupo td { background-color: #e9f1f6; font-weight: bold; }
  .detalle td.nombre { padding-left: 24px; color: #555; }
  .categoria td.nombre { padding-left: 12px; }
  .total td { font-weight: bold; border-top: 2px solid #0A5D86; }
  .rojo { color: #c0392b; }
  .titulo { font-size: 14px; font-weight: bold; color: #0A5D86; text-align: center; margin: 4px 0 0 0; }
  .subtitulo { text-align: center; color: #555; margin: 2px 0 6px 0; }
  .nota { color: #666; font-size: 9px; }
</style>
<table style="border:none; margin-top:0"><tr>
  <td style="border:none; width:20%">' . $logo . '</td>
  <td style="border:none"><b>' . pdf_esc($e['emp_razon'] ?? '') . '</b>' . $trib . '<br>' . pdf_esc($e['emp_direccion'] ?? '') . '</td>
  <td style="border:none; text-align:right; width:25%">Emitido: ' . date('d-m-Y H:i') . '<br>Moneda: ' . pdf_esc(config_regional()['moneda'] ?? '') . '</td>
</tr></table>
<p class="titulo">' . pdf_esc($titulo) . '</p>' . ($subtitulo !== '' ? '<p class="subtitulo">' . pdf_esc($subtitulo) . '</p>' : '');
}

/** Copia los parámetros GET del reporte a $_POST para validarlos con utilitario/validacion.php. */
function pdf_parametros(array $campos) {
    foreach ($campos as $c) {
        $_POST[$c] = $_GET[$c] ?? '';
    }
}
