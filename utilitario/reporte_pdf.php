<?php
/**
 * Piezas comunes de TODOS los reportes PDF (Fase 7; identidad del colegio en Fase 13, paso 11).
 * Escudo, nombre, lema y color salen de Configuración → Apariencia; dirección, teléfono y código, de los datos
 * del colegio. Una sola fuente (DejaVu Sans, con tildes y ñ) y un encabezado común.
 * Se usa desde view/MPDF/REPORTE/*.php (las rutas de imagen son relativas a esa carpeta).
 */
require_once __DIR__ . '/regional.php';
require_once __DIR__ . '/../model/model_conexion.php';
require_once __DIR__ . '/tema.php';

function pdf_esc($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Texto guardado (a veces con entidades HTML) listo para el PDF. */
function pdf_texto($v) {
    return pdf_esc(html_entity_decode((string) $v, ENT_QUOTES, 'UTF-8'));
}

function pdf_fecha($f) {
    return $f && substr((string) $f, 0, 4) !== '0000' ? date('d/m/Y', strtotime($f)) : '';
}

/** Nombre guardado en MAYÚSCULAS en formato título ("Juan de la Cruz", "IV Bimestre", "Primer Grado A"). */
function pdf_titulo($t) {
    $t = mb_convert_case(mb_strtolower(html_entity_decode((string) $t, ENT_QUOTES, 'UTF-8'), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $t = preg_replace_callback('/ (De|Del|La|Las|Los|El|Y|E|En|Con|Por|Para|Al|A)\b(?!$)/u', function ($m) { return ' ' . mb_strtolower($m[1], 'UTF-8'); }, $t);
    return preg_replace_callback('/\b(i{1,3}|iv|vi{0,3}|ix|x|xi{1,3}|xiv)\b/iu', function ($m) { return mb_strtoupper($m[1], 'UTF-8'); }, $t);
}

/** Identidad del colegio para los documentos. */
function pdf_identidad() {
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $t = tema_actual();
    $e = [];
    try {
        $e = (new conexionBD())->conexionPDO()->query("SELECT e.emp_cod, e.emp_direccion, e.emp_telefono, e.emp_email, e.identificacion_tributaria, pa.id_tributario
                                                      FROM empresa e LEFT JOIN paises pa ON pa.id_pais = e.pais_id ORDER BY e.empresa_id LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $x) {
        // sin estos datos el documento sale igual, con el nombre y el escudo
    }
    $id = [
        'nombre' => $t['nombre'], 'lema' => $t['lema'], 'logo' => $t['logo'],
        'color' => $t['primario'], 'acento' => $t['acento'], 'texto_color' => tema_texto_sobre($t['primario']),
        'claro' => tema_mezclar($t['primario'], '#FFFFFF', 0.9),
        'direccion' => $e['emp_direccion'] ?? '', 'telefono' => $e['emp_telefono'] ?? '', 'email' => $e['emp_email'] ?? '',
        'codigo' => $e['emp_cod'] ?? '',
        'tributario' => !empty($e['identificacion_tributaria']) ? trim(($e['id_tributario'] ?? '') . ': ' . $e['identificacion_tributaria'], ': ') : '',
    ];
    return $id;
}

/** Estilos base de todos los PDF: la fuente, las tablas y los colores del colegio. */
function pdf_estilos($tamano = 10) {
    $i = pdf_identidad();
    return '<style>
  body { font-family: dejavusans; font-size: ' . $tamano . 'px; color: #222; }
  table { width: 100%; border-collapse: collapse; }
  .tabla th { background-color: ' . $i['color'] . '; color: ' . $i['texto_color'] . '; border: 1px solid ' . $i['color'] . '; padding: 5px; font-size: ' . ($tamano - 1) . 'px; }
  .tabla td { border: 1px solid #ccc; padding: 4px 5px; }
  .cab td { border: none; vertical-align: middle; padding: 0; }
  .pdf-colegio { font-size: ' . ($tamano + 5) . 'px; font-weight: bold; color: ' . $i['color'] . '; }
  .pdf-lema { color: #666; font-style: italic; font-size: ' . ($tamano - 1) . 'px; }
  .pdf-datos { color: #555; font-size: ' . ($tamano - 1.5) . 'px; }
  .pdf-doc { font-size: ' . ($tamano + 3) . 'px; font-weight: bold; text-align: right; color: ' . $i['color'] . '; }
  .pdf-doc span { font-size: ' . $tamano . 'px; color: #444; font-weight: normal; }
  .pdf-linea { border-bottom: 2px solid ' . $i['color'] . '; margin: 6px 0 10px; }
  .etq { background-color: ' . $i['claro'] . '; font-weight: bold; }
  .c { text-align: center; } .der { text-align: right; }
  .rojo, .mal { color: #B23A2A; font-weight: bold; }
  .nota { color: #666; font-size: ' . ($tamano - 1.5) . 'px; }
  .firmas td { border: none; text-align: center; padding: 50px 6px 0; font-size: ' . ($tamano - 1) . 'px; color: #333; }
</style>';
}

/** Línea de datos del colegio (dirección, teléfono, código, identificación tributaria). */
function pdf_datos_colegio() {
    $i = pdf_identidad();
    $partes = array_filter([$i['direccion'], $i['telefono'] ? 'Tel. ' . $i['telefono'] : '', $i['codigo'] ? 'Código ' . $i['codigo'] : '', $i['tributario']]);
    return pdf_esc(implode(' · ', $partes));
}

/**
 * Encabezado común: escudo, nombre, lema y datos del colegio a la izquierda; el documento a la derecha.
 * $compacto: para tickets angostos (80 mm), todo centrado y apilado.
 */
function pdf_cabecera($documento, $detalle = '', $compacto = false) {
    $i = pdf_identidad();
    $logo = $i['logo'] ? '<img src="../../../' . pdf_esc($i['logo']) . '" style="max-height:' . ($compacto ? 40 : 52) . 'px; max-width:' . ($compacto ? 90 : 120) . 'px">' : '';
    if ($compacto) {
        return '<div style="text-align:center">' . $logo . '<div class="pdf-colegio" style="font-size:12px">' . pdf_esc($i['nombre']) . '</div>'
            . '<div class="pdf-datos">' . pdf_datos_colegio() . '</div>'
            . '<div class="pdf-doc" style="text-align:center; margin-top:4px">' . pdf_esc($documento) . ($detalle !== '' ? '<br><span>' . pdf_esc($detalle) . '</span>' : '') . '</div></div>'
            . '<div class="pdf-linea"></div>';
    }
    return '<table class="cab"><tr><td style="width:130px">' . $logo . '</td><td><div class="pdf-colegio">' . pdf_esc($i['nombre']) . '</div>'
        . ($i['lema'] ? '<div class="pdf-lema">' . pdf_esc($i['lema']) . '</div>' : '')
        . '<div class="pdf-datos">' . pdf_datos_colegio() . '</div></td>'
        . '<td class="pdf-doc">' . pdf_esc($documento) . ($detalle !== '' ? '<br><span>' . pdf_esc($detalle) . '</span>' : '') . '</td></tr></table><div class="pdf-linea"></div>';
}

/** Documento mPDF con la fuente común y márgenes uniformes. $formato: 'A4', 'A4-L' o [ancho, alto] en mm. */
function pdf_nuevo($formato = 'A4', array $extra = []) {
    $angosto = is_array($formato) && $formato[0] < 150;
    $m = $angosto ? 4 : 12;
    $mpdf = new \Mpdf\Mpdf($extra + ['mode' => 'UTF-8', 'format' => $formato, 'default_font' => 'dejavusans',
                                     'margin_left' => $m, 'margin_right' => $m, 'margin_top' => $angosto ? 4 : 10, 'margin_bottom' => $angosto ? 4 : 10]);
    $mpdf->SetCreator(pdf_identidad()['nombre']);
    return $mpdf;
}

/** Encabezado de los reportes financieros (estado de resultados, flujo de caja, morosidad). */
function pdf_encabezado($mysqli, $titulo, $subtitulo = '') {
    $i = pdf_identidad();
    return pdf_estilos(10) . '
<style>
  table { margin-top: 6px; }
  th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
  th { background-color: ' . $i['color'] . '; color: ' . $i['texto_color'] . '; }
  .grupo td { background-color: ' . $i['claro'] . '; font-weight: bold; }
  .detalle td.nombre { padding-left: 24px; color: #555; }
  .categoria td.nombre { padding-left: 12px; }
  .total td { font-weight: bold; border-top: 2px solid ' . $i['color'] . '; }
  .cab td { border: none; }
</style>' . pdf_cabecera($titulo, trim($subtitulo . ' · Emitido el ' . date('d/m/Y H:i') . ' · Moneda: ' . (config_regional()['moneda'] ?? ''), ' ·'));
}

/** Copia los parámetros GET del reporte a $_POST para validarlos con utilitario/validacion.php. */
function pdf_parametros(array $campos) {
    foreach ($campos as $c) {
        $_POST[$c] = $_GET[$c] ?? '';
    }
}
