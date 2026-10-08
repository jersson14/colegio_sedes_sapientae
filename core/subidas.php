<?php

declare(strict_types=1);

/**
 * Validación centralizada de subidas (Fase 0, punto 0.3).
 *
 * Reglas:
 * - El NOMBRE del archivo lo decide el servidor, nunca el cliente.
 * - Imágenes: tipo comprobado por contenido (finfo + getimagesize), no por extensión.
 * - Documentos: lista blanca de extensiones y una sola extensión por nombre.
 * - Borrados: solo dentro de la carpeta esperada y nunca las imágenes por defecto.
 * - Si algo no cuadra se responde 422 ANTES de tocar la base de datos.
 *
 * Requiere core/guard.php (usa responder_error()).
 */

const SUBIDA_RAIZ = __DIR__ . '/..';

const IMAGEN_TIPOS = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
const IMAGEN_MAX_BYTES = 5 * 1024 * 1024;

const DOCUMENTO_EXTENSIONES = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar',
];
const DOCUMENTO_MAX_BYTES = 20 * 1024 * 1024;

// Imágenes por defecto de la aplicación: jamás se borran.
const IMAGENES_PROTEGIDAS = ['vacio.png', 'usuario.png', 'loj.jpg'];

function subida_rechazar(string $mensaje): never
{
    responder_error(422, $mensaje);
}

/** Comprueba un elemento de $_FILES ya normalizado. */
function subida_comprobar(array $f, int $maxBytes): void
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        subida_rechazar('No se pudo recibir el archivo (código ' . (int)($f['error'] ?? -1) . ').');
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        subida_rechazar('Archivo de subida no válido.');
    }
    if ($f['size'] > $maxBytes) {
        subida_rechazar('El archivo supera el tamaño máximo de ' . intdiv($maxBytes, 1048576) . ' MB.');
    }
}

/**
 * Valida $_FILES[$campo] como imagen y devuelve un nombre seguro generado
 * por el servidor (todavía no lo mueve: eso se hace tras guardar en la BD).
 */
function imagen_validada(string $campo = 'foto'): string
{
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || is_array($f['name'] ?? null)) {
        subida_rechazar('No se recibió la imagen.');
    }
    subida_comprobar($f, IMAGEN_MAX_BYTES);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset(IMAGEN_TIPOS[$mime]) || @getimagesize($f['tmp_name']) === false) {
        subida_rechazar('Solo se permiten imágenes JPG, PNG, GIF o WEBP.');
    }
    return 'IMG' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . IMAGEN_TIPOS[$mime];
}

/** Mueve la imagen validada a $directorio (relativo a la raíz del proyecto). */
function imagen_guardar(string $campo, string $directorio, string $nombre): bool
{
    return move_uploaded_file($_FILES[$campo]['tmp_name'], SUBIDA_RAIZ . '/' . $directorio . '/' . $nombre);
}

/**
 * Borra una foto anterior solo si está dentro de $directorio
 * (relativo a la raíz) y no es una imagen por defecto.
 * $rutaRelativa viene del cliente o de la BD: no se confía en ella.
 */
function borrar_archivo_subido(string $rutaRelativa, string $directorio): void
{
    $dir = realpath(SUBIDA_RAIZ . '/' . $directorio);
    $archivo = realpath(SUBIDA_RAIZ . '/' . html_entity_decode($rutaRelativa, ENT_QUOTES, 'UTF-8'));
    if ($dir === false || $archivo === false || !is_file($archivo)) {
        return;
    }
    if (!str_starts_with($archivo, $dir . DIRECTORY_SEPARATOR)) {
        return;
    }
    if (in_array(strtolower(basename($archivo)), IMAGENES_PROTEGIDAS, true)) {
        return;
    }
    unlink($archivo);
}

/**
 * Valida los documentos de $_FILES[$campo] (input múltiple) y devuelve
 * [['tmp' => ..., 'nombre' => ...], ...] con nombres saneados y únicos.
 * Se conserva el nombre original (útil al descargar) pero solo con letras,
 * números, espacio, guion y guion bajo, y UNA extensión de la lista blanca.
 */
function documentos_validados(string $campo = 'archivos'): array
{
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || !is_array($f['name'] ?? null)) {
        return [];
    }
    $salida = [];
    $usados = [];
    foreach ($f['name'] as $i => $original) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $original === '') {
            continue;
        }
        $item = ['error' => $f['error'][$i], 'tmp_name' => $f['tmp_name'][$i], 'size' => $f['size'][$i]];
        subida_comprobar($item, DOCUMENTO_MAX_BYTES);

        $nombre = nombre_documento_seguro($original, $usados);
        if ($nombre === null) {
            subida_rechazar('Tipo de archivo no permitido: '
                . htmlspecialchars(basename(str_replace('\\', '/', $original)), ENT_QUOTES, 'UTF-8')
                . '. Permitidos: ' . implode(', ', DOCUMENTO_EXTENSIONES) . '.');
        }
        $salida[] = ['tmp' => $item['tmp_name'], 'nombre' => $nombre];
    }
    return $salida;
}

/**
 * Nombre seguro para un documento subido, o null si su extensión no está permitida.
 * Conserva el nombre original (útil al descargar) pero solo con letras, números,
 * espacio, guion y guion bajo, y UNA extensión de la lista blanca. $usados evita
 * colisiones dentro de la misma subida (NOMBRE.PDF, NOMBRE_2.PDF…).
 *
 * @param array<string, true> $usados
 */
function nombre_documento_seguro(string $original, array &$usados): ?string
{
    $original = basename(str_replace('\\', '/', $original));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, DOCUMENTO_EXTENSIONES, true)) {
        return null;
    }
    $base = pathinfo($original, PATHINFO_FILENAME);
    $base = preg_replace('/[^\p{L}\p{N} _-]+/u', '_', $base) ?? '';
    $base = mb_substr(trim($base, " _-"), 0, 100);
    if ($base === '') {
        $base = 'ARCHIVO';
    }
    $nombre = mb_strtoupper($base . '.' . $ext, 'UTF-8');
    for ($n = 2; isset($usados[$nombre]); $n++) {
        $nombre = mb_strtoupper($base . '_' . $n . '.' . $ext, 'UTF-8');
    }
    $usados[$nombre] = true;
    return $nombre;
}

/**
 * Devuelve la carpeta de tarea solo si su nombre tiene la forma que genera
 * el sistema (tarea_alumnos_<timestamp> o <timestamp>); si no, null.
 */
function carpeta_tarea_valida(string $ruta): ?string
{
    $nombre = basename(str_replace('\\', '/', $ruta));
    // Marca de tiempo (carpetas antiguas) o marca de tiempo + sufijo aleatorio (App\Support\DocumentosTarea:
    // con solo segundos, dos entregas del mismo segundo compartían carpeta).
    return preg_match('/^(tarea_alumnos_)?\d{9,11}(_[0-9a-f]{8})?$/', $nombre) ? $nombre : null;
}
