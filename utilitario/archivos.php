<?php
/**
 * Validación de archivos subidos y borrado seguro (Fase 1).
 * Requiere que antes se haya incluido controller/_seguridad.php (usa denegar()).
 */

const FOTO_MAX_MB = 5;
const DOCUMENTO_MAX_MB = 20;
const DOCUMENTO_EXTENSIONES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
// Imágenes por defecto que nunca se borran
const ARCHIVOS_PROTEGIDOS = ['vacio.png', 'admin.png', 'usuario.png', 'loj.jpg'];

/**
 * Valida la foto enviada en $_FILES[$campo] y devuelve un nombre generado en el servidor.
 * El nombre que propone el navegador solo indica si hay foto nueva: vacío (o una ruta de
 * carpeta terminada en "/") significa "sin foto nueva" y se devuelve tal cual.
 */
function preparar_foto_subida($campo, $nombre_cliente) {
    if ($nombre_cliente === '' || substr($nombre_cliente, -1) === '/') {
        return $nombre_cliente;
    }
    $f = $_FILES[$campo] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return '';
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($tipos[$mime]) || $f['size'] > FOTO_MAX_MB * 1024 * 1024 || @getimagesize($f['tmp_name']) === false) {
        denegar(400, 'Solo se permiten imágenes JPG, PNG, GIF o WEBP de hasta ' . FOTO_MAX_MB . ' MB');
    }
    return 'IMG' . date('d-m-Y-H') . '-' . bin2hex(random_bytes(4)) . '.' . $tipos[$mime];
}

// Videos de las tareas (docente/administrador). El tamaño máximo sale de config.php ('video_max_mb')
// y nunca supera lo que el servidor acepta (upload_max_filesize / post_max_size).
const VIDEO_EXTENSIONES = ['mp4', 'webm', 'm4v', 'mov'];
const VIDEO_MAX_MB_DEFECTO = 100;
const ENLACES_VIDEO_MAX = 10;

/** Valor de php.ini como "40M" / "1G" en megabytes. */
function ini_en_mb($clave) {
    $v = trim((string) ini_get($clave));
    if ($v === '' || $v === '0' || $v === '-1') {
        return PHP_INT_MAX;
    }
    $n = (float) $v;
    switch (strtoupper(substr($v, -1))) {
        case 'G': return (int) ($n * 1024);
        case 'M': return (int) $n;
        case 'K': return (int) ($n / 1024);
        default:  return (int) ($n / 1048576);
    }
}

/** Tamaño máximo (MB) de un video, limitado por lo que acepta el servidor. */
function limite_video_mb() {
    static $limite = null;
    if ($limite === null) {
        $config = require __DIR__ . '/../config/config.php';
        $limite = min((int) ($config['video_max_mb'] ?? VIDEO_MAX_MB_DEFECTO), ini_en_mb('upload_max_filesize'), ini_en_mb('post_max_size'));
    }
    return $limite;
}

/** Tamaño máximo (MB) de un documento, limitado por lo que acepta el servidor. */
function limite_documento_mb() {
    return min(DOCUMENTO_MAX_MB, ini_en_mb('upload_max_filesize'), ini_en_mb('post_max_size'));
}

function es_video($nombre) {
    return in_array(strtolower(pathinfo($nombre, PATHINFO_EXTENSION)), VIDEO_EXTENSIONES, true);
}

/**
 * Revisa extensión y tamaño de cada archivo de $_FILES[$campo] (subida múltiple).
 * Con $permitir_video también acepta videos (con su propio límite y revisando que el contenido sea video).
 */
function validar_documentos_subidos($campo, $permitir_video = false) {
    // Si el envío superó post_max_size PHP descarta todo: avisar en vez de guardar la tarea sin archivos
    if (empty($_FILES) && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        denegar(400, 'Los archivos superan el tamaño que acepta el servidor (' . ini_get('post_max_size') . ' en total)');
    }
    if (empty($_FILES[$campo]['name'])) {
        return;
    }
    $permitidas = $permitir_video ? array_merge(DOCUMENTO_EXTENSIONES, VIDEO_EXTENSIONES) : DOCUMENTO_EXTENSIONES;
    foreach ((array) $_FILES[$campo]['name'] as $i => $nombre) {
        if ($nombre === '') {
            continue;
        }
        $seguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $tam = ((array) $_FILES[$campo]['size'])[$i] ?? 0;
        $error = ((array) $_FILES[$campo]['error'])[$i] ?? UPLOAD_ERR_OK;
        if (!in_array($ext, $permitidas, true)) {
            denegar(400, 'Tipo de archivo no permitido: ' . $seguro . '. Permitidos: ' . implode(', ', $permitidas));
        }
        $video = in_array($ext, VIDEO_EXTENSIONES, true);
        $max = $video ? limite_video_mb() : limite_documento_mb();
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || $tam > $max * 1024 * 1024) {
            denegar(400, 'El archivo supera los ' . $max . ' MB: ' . $seguro);
        }
        if ($error !== UPLOAD_ERR_OK) {
            denegar(400, 'No se pudo recibir el archivo (subida incompleta): ' . $seguro);
        }
        if ($video) {
            $tmp = ((array) $_FILES[$campo]['tmp_name'])[$i] ?? '';
            $mime = is_uploaded_file($tmp) ? (new finfo(FILEINFO_MIME_TYPE))->file($tmp) : '';
            if (strpos((string) $mime, 'video/') !== 0) {
                denegar(400, 'El archivo no es un video válido: ' . $seguro);
            }
        }
    }
}

/**
 * Enlaces de video enviados por el formulario (uno por línea). Solo http/https, sin repetir, máximo
 * ENLACES_VIDEO_MAX. Devuelve el JSON a guardar en tareas.enlaces_video (null si no hay enlaces).
 */
function enlaces_video_desde_texto($texto) {
    $enlaces = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $linea) {
        $url = trim($linea);
        if ($url === '') {
            continue;
        }
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL) || !$host || strpos($host, '.') === false) {
            denegar(400, 'Enlace de video no válido: ' . htmlspecialchars($linea, ENT_QUOTES, 'UTF-8'));
        }
        // el mismo video escrito de dos formas (youtu.be/x y youtube.com/watch?v=x) se guarda una vez
        $clave = video_incrustado($url) ?: $url;
        $enlaces[$clave] = $enlaces[$clave] ?? ['url' => $url];
    }
    if (count($enlaces) > ENLACES_VIDEO_MAX) {
        denegar(400, 'Puede agregar hasta ' . ENLACES_VIDEO_MAX . ' enlaces de video por tarea');
    }
    return $enlaces ? json_encode(array_values($enlaces), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
}

/** Lista de enlaces guardada en tareas.enlaces_video. */
function enlaces_video_lista($json) {
    $lista = json_decode((string) $json, true);
    return is_array($lista) ? array_values(array_filter($lista, function ($e) { return !empty($e['url']); })) : [];
}

/**
 * Dirección para reproducir el enlace dentro de la plataforma (YouTube, Google Drive o Vimeo).
 * Se arma solo con el identificador del video; '' si el sitio no se puede incrustar (se muestra como enlace).
 */
function video_incrustado($url) {
    $p = parse_url($url);
    $host = strtolower(preg_replace('/^(www\.|m\.)/i', '', $p['host'] ?? ''));
    $ruta = $p['path'] ?? '';
    parse_str($p['query'] ?? '', $q);
    if ($host === 'youtu.be' && preg_match('~^/([\w-]{11})~', $ruta, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    }
    if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
        if (!empty($q['v']) && is_string($q['v']) && preg_match('/^[\w-]{11}$/', $q['v'])) {
            return 'https://www.youtube-nocookie.com/embed/' . $q['v'];
        }
        if (preg_match('~^/(embed|shorts|live)/([\w-]{11})~', $ruta, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[2];
        }
    }
    if ($host === 'drive.google.com') {
        if (preg_match('~/file/d/([\w-]{10,})~', $ruta, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }
        if (!empty($q['id']) && is_string($q['id']) && preg_match('/^[\w-]{10,}$/', $q['id'])) {
            return 'https://drive.google.com/file/d/' . $q['id'] . '/preview';
        }
    }
    if ($host === 'vimeo.com' && preg_match('~^/(\d+)~', $ruta, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    if ($host === 'player.vimeo.com' && preg_match('~^/video/(\d+)~', $ruta, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return '';
}

/** Nombre de archivo sin rutas ni caracteres especiales (conserva letras con tilde, números, espacios, . _ - ()). */
function nombre_documento_seguro($nombre) {
    $n = basename(str_replace('\\', '/', $nombre));
    $n = preg_replace('/[^\p{L}\p{N} ._()\-]/u', '_', $n);
    $n = trim($n, ' .');
    return $n === '' ? 'archivo' : $n;
}

/**
 * Nombre de la carpeta de una tarea (formato generado por el sistema: "1723070700" o
 * "tarea_alumnos_1729369076"). Devuelve '' si no tiene ese formato, para no borrar otra carpeta.
 */
function carpeta_tarea_valida($ruta) {
    $b = basename(str_replace('\\', '/', (string) $ruta));
    return preg_match('/^(tarea_alumnos_)?\d+$/', $b) ? $b : '';
}

/**
 * Crea una carpeta nueva y exclusiva para los archivos de una tarea (material del docente o envío del estudiante)
 * y devuelve su ruta tal como se guarda en la base ("controller/tareas/documentos/tarea_alumnos_…").
 * Antes el nombre era solo la hora en segundos: dos envíos en el mismo segundo compartían carpeta.
 */
function crear_carpeta_tarea() {
    $base = __DIR__ . '/../controller/tareas/controller/tareas/documentos/';
    if (!is_dir($base)) {
        mkdir($base, 0777, true);
    }
    do {
        $nombre = 'tarea_alumnos_' . time() . random_int(100000, 999999);
    } while (!@mkdir($base . $nombre, 0777)); // mkdir falla si ya existe: se prueba otro nombre
    return 'controller/tareas/documentos/' . $nombre;
}

/**
 * Voucher de pago opcional ($_FILES[$campo]): imagen o PDF de hasta 5 MB.
 * Devuelve null si no se envió; si es inválido responde 400. Se valida ANTES de registrar el pago.
 */
function validar_voucher_subido($campo) {
    $f = $_FILES[$campo] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $mime = ($f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
    if (!isset($tipos[$mime]) || $f['size'] > FOTO_MAX_MB * 1024 * 1024) {
        denegar(400, 'El voucher debe ser una imagen (JPG, PNG, WEBP) o PDF de hasta ' . FOTO_MAX_MB . ' MB');
    }
    return ['tmp' => $f['tmp_name'], 'ext' => $tipos[$mime]];
}

/** Guarda el voucher validado en controller/pago_pension/vouchers y devuelve su ruta relativa a la raíz. */
function guardar_voucher($voucher) {
    $dir = __DIR__ . '/../controller/pago_pension/vouchers';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $nombre = 'VCH-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $voucher['ext'];
    return move_uploaded_file($voucher['tmp'], $dir . '/' . $nombre) ? 'controller/pago_pension/vouchers/' . $nombre : null;
}

/** Guarda un archivo ya validado (validar_voucher_subido) en $carpeta_relativa; devuelve su ruta relativa a la raíz. */
function guardar_archivo_validado($archivo, $carpeta_relativa, $prefijo) {
    $dir = __DIR__ . '/../' . $carpeta_relativa;
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $nombre = $prefijo . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $archivo['ext'];
    return move_uploaded_file($archivo['tmp'], $dir . '/' . $nombre) ? $carpeta_relativa . '/' . $nombre : null;
}

/**
 * Borra un archivo solo si está dentro de $carpeta_relativa (rutas relativas a la raíz del sistema)
 * y no es una imagen por defecto. Evita que una ruta enviada por el navegador borre otros archivos.
 */
function borrar_archivo_seguro($ruta_relativa, $carpeta_relativa) {
    $raiz = realpath(__DIR__ . '/..');
    $carpeta = realpath($raiz . '/' . $carpeta_relativa);
    $archivo = realpath($raiz . '/' . $ruta_relativa);
    if (!$carpeta || !$archivo || !is_file($archivo)) {
        return false;
    }
    if (stripos($archivo, $carpeta . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }
    if (in_array(strtolower(basename($archivo)), ARCHIVOS_PROTEGIDOS, true)) {
        return false;
    }
    return @unlink($archivo);
}
