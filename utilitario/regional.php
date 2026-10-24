<?php
/**
 * Configuración regional del colegio (Fase 3): país, moneda, zona horaria y documento de identidad;
 * escala de notas (Fase 8C).
 * Se guarda en la tabla empresa y se configura en Configuración > Datos regionales.
 * Se lee una vez y se guarda en la sesión; tras modificarla se llama a config_regional(true).
 */

function config_regional_por_defecto() {
    return [
        'pais' => 'PE', 'pais_nombre' => 'Perú', 'locale' => 'es-PE', 'zona_horaria' => 'America/Lima',
        'prefijo_tel' => '+51', 'id_tributario' => 'RUC', 'identificacion_tributaria' => '',
        'moneda' => 'PEN', 'moneda_nombre' => 'Sol peruano', 'simbolo' => 'S/', 'decimales' => 2,
        'sep_miles' => ',', 'sep_decimal' => '.', 'simbolo_antes' => 1,
        'doc_nombre' => 'DNI', 'doc_min' => 8, 'doc_max' => 8, 'doc_regex' => '^[0-9]{8}$', 'doc_ejemplo' => '12345678',
        'permite_doc_extranjero' => 1,
        'nota_min' => 0, 'nota_max' => 20, 'nota_aprobatoria' => 11, 'nota_decimales' => 0,
        'notas_literales' => 'AD,A,B,C', 'literales_aprobatorios' => 'AD,A,B',
    ];
}

/** Olvida la configuración leída en esta petición (la próxima llamada la toma de la sesión o la BD). */
function regional_reiniciar_cache() {
    unset($GLOBALS['__config_regional']);
}

function config_regional($refrescar = false) {
    if (!$refrescar && isset($GLOBALS['__config_regional'])) {
        return $GLOBALS['__config_regional'];
    }
    // Una sesión iniciada antes de la Fase 8C no trae la escala de notas: se vuelve a leer de la BD
    if (!$refrescar && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['S_REGIONAL']['nota_max'])) {
        return $GLOBALS['__config_regional'] = $_SESSION['S_REGIONAL'];
    }
    $cfg = config_regional_por_defecto();
    try {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['db'];
        $cfg['zona_horaria'] = $config['zona_horaria'];
        $pdo = new PDO("mysql:host={$db['host']};port={$db['puerto']};dbname={$db['nombre']};charset=utf8mb4",
                       $db['usuario'], $db['contrasena'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = "SELECT p.codigo_iso AS pais, p.nombre AS pais_nombre, p.locale, p.prefijo_tel, p.id_tributario,
                       IFNULL(e.zona_horaria, p.zona_horaria) AS zona_horaria,
                       IFNULL(e.identificacion_tributaria, '') AS identificacion_tributaria,
                       m.codigo AS moneda, m.nombre AS moneda_nombre, m.simbolo, m.decimales, m.sep_miles, m.sep_decimal, m.simbolo_antes,
                       t.nombre AS doc_nombre, t.long_min AS doc_min, t.long_max AS doc_max, t.regex AS doc_regex, t.ejemplo AS doc_ejemplo,
                       e.permite_doc_extranjero,
                       IFNULL(e.nota_min, p.nota_min) AS nota_min, IFNULL(e.nota_max, p.nota_max) AS nota_max,
                       IFNULL(e.nota_aprobatoria, p.nota_aprobatoria) AS nota_aprobatoria,
                       IFNULL(e.nota_decimales, p.nota_decimales) AS nota_decimales,
                       IFNULL(e.notas_literales, p.notas_literales) AS notas_literales,
                       IFNULL(e.literales_aprobatorios, p.literales_aprobatorios) AS literales_aprobatorios
                  FROM empresa e
                  INNER JOIN paises p ON p.id_pais = e.pais_id
                  INNER JOIN monedas m ON m.id_moneda = IFNULL(e.moneda_id, p.moneda_id)
                  LEFT JOIN tipos_documento_identidad t ON t.pais_id = p.id_pais
                 ORDER BY e.empresa_id, t.id_tipo_doc LIMIT 1";
        $fila = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            foreach (['decimales', 'doc_min', 'doc_max', 'simbolo_antes', 'permite_doc_extranjero', 'nota_decimales'] as $k) {
                $fila[$k] = (int) $fila[$k];
            }
            foreach (['nota_min', 'nota_max', 'nota_aprobatoria'] as $k) {
                $fila[$k] = (float) $fila[$k];
            }
            $cfg = array_merge($cfg, array_filter($fila, function ($v) { return $v !== null; }));
        }
    } catch (Throwable $e) {
        // Sin BD o sin la migración 003: se usan los valores por defecto (Perú)
        error_log('config_regional: ' . $e->getMessage());
    }
    if (!in_array($cfg['zona_horaria'], timezone_identifiers_list(), true)) {
        $cfg['zona_horaria'] = 'America/Lima';
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['S_REGIONAL'] = $cfg;
    }
    return $GLOBALS['__config_regional'] = $cfg;
}

/** 1500.5 -> "S/ 1,500.50" (Perú), "$ 1.500,50" (Argentina), "$ 1.501" (Chile, sin decimales). */
function formato_moneda($monto, $con_simbolo = true) {
    $c = config_regional();
    $texto = number_format((float) $monto, $c['decimales'], $c['sep_decimal'], $c['sep_miles']);
    if (!$con_simbolo) {
        return $texto;
    }
    return $c['simbolo_antes'] ? $c['simbolo'] . ' ' . $texto : $texto . ' ' . $c['simbolo'];
}

function nombre_documento() {
    return config_regional()['doc_nombre'];
}

/** Normaliza un documento: mayúsculas, sin espacios ni puntos (los guiones se conservan). */
function normalizar_documento($valor) {
    return strtoupper(str_replace([' ', '.'], '', trim((string) $valor)));
}

/** true si el documento cumple el formato del país (o pasaporte/carné si está permitido). */
function documento_valido($valor) {
    $c = config_regional();
    $v = normalizar_documento($valor);
    if (preg_match('/' . str_replace('/', '\/', $c['doc_regex']) . '/u', $v)) {
        return true;
    }
    // Pasaporte / carné de extranjería: alfanumérico, con alguna letra o más largo que el documento local
    // (así un DNI con un dígito de menos no pasa como "extranjero")
    return $c['permite_doc_extranjero'] && preg_match('/^[A-Z0-9-]{4,20}$/', $v)
        && (preg_match('/[A-Z]/', $v) || strlen($v) > $c['doc_max']);
}

/** Teléfono: dígitos con +, espacios, guiones o paréntesis; entre 6 y 15 dígitos. */
function telefono_valido($valor) {
    $v = trim((string) $valor);
    $digitos = preg_replace('/\D/', '', $v);
    return preg_match('/^\+?[0-9 ()-]+$/', $v) && strlen($digitos) >= 6 && strlen($digitos) <= 15 && strlen($v) <= 20;
}

/** Datos que se envían al navegador (window.REGIONAL). */
function config_regional_publica() {
    $c = config_regional();
    return array_intersect_key($c, array_flip([
        'pais', 'pais_nombre', 'locale', 'zona_horaria', 'prefijo_tel', 'id_tributario', 'moneda', 'simbolo', 'decimales',
        'sep_miles', 'sep_decimal', 'simbolo_antes', 'doc_nombre', 'doc_min', 'doc_max', 'doc_regex', 'doc_ejemplo',
        'permite_doc_extranjero', 'nota_min', 'nota_max', 'nota_aprobatoria', 'nota_decimales', 'notas_literales', 'literales_aprobatorios',
    ]));
}

/** Lista de calificaciones literales configuradas (p. ej. ['AD','A','B','C']). */
function notas_literales($clave = 'notas_literales') {
    $t = trim((string) (config_regional()[$clave] ?? ''));
    return $t === '' ? [] : array_map('trim', explode(',', strtoupper($t)));
}

/**
 * Normaliza una nota según la escala del colegio: número dentro del rango con los decimales permitidos
 * (acepta coma decimal) o una calificación literal configurada. Devuelve null si no es válida.
 */
function nota_normalizada($valor) {
    $c = config_regional();
    $v = strtoupper(trim(str_replace(',', '.', (string) $valor)));
    if ($v === '') {
        return null;
    }
    if (in_array($v, notas_literales(), true)) {
        return $v;
    }
    if (!preg_match('/^\d+(\.\d+)?$/', $v)) {
        return null;
    }
    $partes = explode('.', $v);
    if (isset($partes[1]) && strlen(rtrim($partes[1], '0')) > $c['nota_decimales']) {
        return null;
    }
    $n = (float) $v;
    if ($n < $c['nota_min'] || $n > $c['nota_max']) {
        return null;
    }
    return number_format($n, $c['nota_decimales'], '.', '');
}

/** true si la nota aprueba (numérica >= nota aprobatoria, o literal aprobatoria). */
function nota_aprobada($valor) {
    $v = nota_normalizada($valor);
    if ($v === null) {
        return false;
    }
    if (!is_numeric($v)) {
        return in_array($v, notas_literales('literales_aprobatorios'), true);
    }
    return (float) $v >= config_regional()['nota_aprobatoria'];
}

/** Texto para mensajes: "0 a 20 (aprueba con 11) o AD, A, B, C". */
function descripcion_escala() {
    $c = config_regional();
    // 20.00 -> "20", 6.50 -> "6.5" (solo se quitan ceros de la parte decimal)
    $f = function ($n) { $t = number_format((float) $n, 2, '.', ''); return rtrim(rtrim($t, '0'), '.'); };
    $t = $f($c['nota_min']) . ' a ' . $f($c['nota_max']) . ' (aprueba con ' . $f($c['nota_aprobatoria']) . ')';
    return notas_literales() ? $t . ' o ' . implode(', ', notas_literales()) : $t;
}
