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

/** Revisa extensión y tamaño de cada archivo de $_FILES[$campo] (subida múltiple). */
function validar_documentos_subidos($campo) {
    if (empty($_FILES[$campo]['name'])) {
        return;
    }
    foreach ((array) $_FILES[$campo]['name'] as $i => $nombre) {
        if ($nombre === '') {
            continue;
        }
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $tam = ((array) $_FILES[$campo]['size'])[$i] ?? 0;
        if (!in_array($ext, DOCUMENTO_EXTENSIONES, true)) {
            denegar(400, 'Tipo de archivo no permitido: ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')
                . '. Permitidos: ' . implode(', ', DOCUMENTO_EXTENSIONES));
        }
        if ($tam > DOCUMENTO_MAX_MB * 1024 * 1024) {
            denegar(400, 'El archivo supera los ' . DOCUMENTO_MAX_MB . ' MB: ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'));
        }
    }
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
