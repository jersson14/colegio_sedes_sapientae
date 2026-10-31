<?php
/**
 * Lectura y creación de hojas de cálculo sin librerías externas (Fase 13, paso 14).
 * - hoja_leer(): primera hoja de un .xlsx (Excel, LibreOffice, Google Sheets) o un .csv (separado por ; o ,).
 * - hoja_crear_xlsx(): un .xlsx con una o más hojas (encabezado en negrita y anchos de columna).
 */

/** Filas de la primera hoja como arreglos de texto. $nombre: nombre original (para saber el formato). */
function hoja_leer($ruta, $nombre, $max_filas = 2000) {
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if ($ext === 'csv' || $ext === 'txt') {
        return hoja_leer_csv($ruta, $max_filas);
    }
    if ($ext === 'xlsx') {
        return hoja_leer_xlsx($ruta, $max_filas);
    }
    throw new InvalidArgumentException('Use un archivo de Excel (.xlsx) o .csv');
}

function hoja_leer_csv($ruta, $max_filas) {
    $texto = file_get_contents($ruta);
    if ($texto === false) { throw new InvalidArgumentException('No se pudo leer el archivo'); }
    $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);                     // marca UTF-8 de Excel
    if (!mb_check_encoding($texto, 'UTF-8')) { $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252'); }
    $primera = strtok($texto, "\n");
    $sep = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';
    $filas = [];
    $f = fopen('php://memory', 'r+');
    fwrite($f, $texto);
    rewind($f);
    while (($fila = fgetcsv($f, 0, $sep, '"', '')) !== false && count($filas) < $max_filas + 1) {
        $filas[] = array_map(function ($v) { return trim((string) $v); }, $fila);
    }
    fclose($f);
    return $filas;
}

function hoja_leer_xlsx($ruta, $max_filas) {
    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) { throw new InvalidArgumentException('El archivo no es un Excel válido (.xlsx)'); }
    $compartidos = [];
    if (($x = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $sx = simplexml_load_string($x);
        foreach ($sx->si as $si) {
            if (isset($si->t)) { $compartidos[] = (string) $si->t; continue; }
            $t = '';
            foreach ($si->r as $r) { $t .= (string) $r->t; }   // texto con formato en partes
            $compartidos[] = $t;
        }
    }
    // La primera hoja según el libro (no siempre se llama sheet1.xml)
    $hoja = 'xl/worksheets/sheet1.xml';
    $wb = $zip->getFromName('xl/workbook.xml');
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb && $rels) {
        $w = simplexml_load_string($wb);
        $w->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $primera = $w->xpath('//m:sheets/m:sheet')[0] ?? null;
        if ($primera) {
            $rid = (string) $primera->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach (simplexml_load_string($rels)->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) { $hoja = 'xl/' . ltrim(preg_replace('#^/?xl/#', '', (string) $rel['Target']), '/'); }
            }
        }
    }
    $xml = $zip->getFromName($hoja);
    $zip->close();
    if ($xml === false) { throw new InvalidArgumentException('El Excel no tiene hojas'); }
    $sx = simplexml_load_string($xml);
    $filas = [];
    foreach ($sx->sheetData->row as $row) {
        $fila = [];
        foreach ($row->c as $c) {
            preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
            $col = hoja_columna_indice($m[1] ?? 'A');
            $tipo = (string) $c['t'];
            if ($tipo === 's') { $v = $compartidos[(int) $c->v] ?? ''; }
            elseif ($tipo === 'inlineStr') { $v = (string) $c->is->t; }
            else { $v = (string) $c->v; }
            $fila[$col] = trim($v);
        }
        if ($fila) {
            $max = max(array_keys($fila));
            $completa = [];
            for ($i = 0; $i <= $max; $i++) { $completa[] = $fila[$i] ?? ''; }
            $filas[(int) $row['r'] - 1] = $completa;
        }
        if (count($filas) > $max_filas + 1) { break; }
    }
    ksort($filas);
    return array_values($filas);
}

function hoja_columna_indice($letras) {
    $n = 0;
    foreach (str_split($letras) as $l) { $n = $n * 26 + (ord($l) - 64); }
    return $n - 1;
}

function hoja_columna_letras($i) {
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) { $s = chr(65 + ($i - 1) % 26) . $s; }
    return $s;
}

/** Fecha de una celda: dd/mm/aaaa, aaaa-mm-dd o número de serie de Excel. Devuelve 'aaaa-mm-dd' o null. */
function hoja_fecha($v) {
    $v = trim((string) $v);
    if ($v === '') { return null; }
    if (preg_match('/^\d{4,5}(\.\d+)?$/', $v)) {                                  // serie de Excel (días desde 1899-12-30)
        return date('Y-m-d', (int) round(((float) $v - 25569) * 86400));
    }
    if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $v, $m)) { $d = (int) $m[1]; $mo = (int) $m[2]; $y = (int) $m[3]; }
    elseif (preg_match('#^(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})#', $v, $m)) { $y = (int) $m[1]; $mo = (int) $m[2]; $d = (int) $m[3]; }
    else { return null; }
    return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
}

/** .xlsx con varias hojas: [['nombre' => 'Estudiantes', 'filas' => [[...], ...], 'anchos' => [14, 20, ...]], ...]. */
function hoja_crear_xlsx(array $hojas) {
    $esc = function ($v) { return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $tipos = '';
    $libro = '';
    $rels = '';
    foreach ($hojas as $i => $h) {
        $n = $i + 1;
        $cols = '';
        foreach ($h['anchos'] ?? [] as $k => $ancho) { $cols .= '<col min="' . ($k + 1) . '" max="' . ($k + 1) . '" width="' . $ancho . '" customWidth="1"/>'; }
        $datos = '';
        foreach ($h['filas'] as $r => $fila) {
            $datos .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($fila) as $k => $v) {
                $datos .= '<c r="' . hoja_columna_letras($k) . ($r + 1) . '" t="inlineStr"' . ($r === 0 && empty($h['sin_encabezado']) ? ' s="1"' : '') . '><is><t xml:space="preserve">' . $esc($v) . '</t></is></c>';
            }
            $datos .= '</row>';
        }
        $zip->addFromString("xl/worksheets/sheet$n.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . (empty($h['sin_encabezado']) ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' : '')
            . ($cols ? "<cols>$cols</cols>" : '') . "<sheetData>$datos</sheetData></worksheet>");
        $tipos .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $libro .= '<sheet name="' . $esc($h['nombre']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
    }
    $k = count($hojas) + 1;
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $tipos . '</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $libro . '</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $rels . '<Relationship Id="rId' . $k . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
        . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
        . '<cellXfs count="2"><xf fontId="0"/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');
    $zip->close();
    $bin = file_get_contents($tmp);
    unlink($tmp);
    return $bin;
}
