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
 *
 * Almacén por institución (Fase 4.6): lo que se sube se guarda en <ALMACEN_DIR>/<slug>/<ruta de la BD>
 * (por defecto storage/tenants/, cerrado por .htaccess). La BD sigue guardando la misma ruta relativa
 * de siempre («controller/alumnos/fotos/IMG….jpg»), y esa URL la sirve controller/archivo/ con sesión.
 * En modo único también se buscan los archivos subidos antes, en su carpeta original; en modo múltiple
 * nunca: un colegio no puede leer ni borrar archivos que no estén en su propio almacén.
 */

require_once __DIR__ . '/tenant.php';

use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

const SUBIDA_RAIZ = __DIR__ . '/..';

/** Carpeta física de la institución de la petición. */
function almacen_raiz(): string
{
    $base = config('ALMACEN_DIR') ?: dirname(__DIR__) . '/storage/tenants';
    return rtrim($base, '/\\') . '/' . tenant_actual()->slug;
}

/**
 * Raíces donde puede estar un archivo de esta institución, en orden: su almacén y, solo en modo
 * único, la ubicación anterior a la Fase 4 (la raíz del proyecto).
 *
 * @return list<string>
 */
function almacen_raices(): array
{
    $raices = [almacen_raiz()];
    if (ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico) {
        $raices[] = SUBIDA_RAIZ;
    }
    return $raices;
}

/**
 * Archivo físico de una ruta guardada en la BD, dentro de $directorio (ambas relativas), o null.
 * $rutaRelativa viene del cliente o de la BD: no se confía en ella (entidades, «..», enlaces).
 */
function subida_ubicar(string $rutaRelativa, string $directorio): ?string
{
    $rutaRelativa = html_entity_decode($rutaRelativa, ENT_QUOTES, 'UTF-8');
    foreach (almacen_raices() as $raiz) {
        $dir = realpath($raiz . '/' . $directorio);
        $archivo = realpath($raiz . '/' . $rutaRelativa);
        if ($dir !== false && $archivo !== false && is_file($archivo)
            && str_starts_with($archivo, $dir . DIRECTORY_SEPARATOR)) {
            return $archivo;
        }
    }
    return null;
}

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

/** Mueve la imagen validada a $directorio (la ruta de la BD) dentro del almacén de la institución. */
function imagen_guardar(string $campo, string $directorio, string $nombre): bool
{
    $destino = almacen_raiz() . '/' . $directorio;
    if (!is_dir($destino) && !mkdir($destino, 0755, true) && !is_dir($destino)) {
        return false;
    }
    return move_uploaded_file($_FILES[$campo]['tmp_name'], $destino . '/' . $nombre);
}

/**
 * Borra una foto anterior solo si está dentro de $directorio
 * (relativo a la raíz) y no es una imagen por defecto.
 * $rutaRelativa viene del cliente o de la BD: no se confía en ella.
 */
function borrar_archivo_subido(string $rutaRelativa, string $directorio): void
{
    $archivo = subida_ubicar($rutaRelativa, $directorio);
    if ($archivo === null || in_array(strtolower(basename($archivo)), IMAGENES_PROTEGIDAS, true)) {
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

/** Ruta de los documentos de tareas tal como la guarda la BD. */
const TAREAS_RUTA_BD = 'controller/tareas/documentos';

/**
 * Carpetas donde quedaron los archivos subidos antes de la Fase 4, con la ruta que les da la BD
 * (tools/mover_subidas.php las traslada; tools/respaldo_tenant.php las incluye en modo único).
 *
 * @return list<array{0: string, 1: string}> [carpeta física, ruta en la BD]
 */
function subidas_anteriores(): array
{
    $raiz = SUBIDA_RAIZ;
    $carpetas = [];
    foreach (['alumnos/fotos', 'docentes/fotos', 'personal_administrativo/fotos', 'comunicados/fotos', 'empleado/FOTOS', 'empresa/FOTOS'] as $c) {
        $carpetas[] = ["$raiz/controller/$c", "controller/$c"];
    }
    $carpetas[] = ["$raiz/controller/tareas/controller/tareas/documentos", TAREAS_RUTA_BD];
    return $carpetas;
}

/** Archivos de la aplicación (no de los usuarios) dentro de esas carpetas. */
function subida_es_de_la_aplicacion(string $nombre): bool
{
    return $nombre === '.htaccess' || in_array(strtolower($nombre), IMAGENES_PROTEGIDAS, true);
}

/**
 * Carpeta física existente de una tarea, o null. $carpeta ya pasó por carpeta_tarea_valida().
 * Antes de la Fase 4 las rutas de la BD se resolvían relativas a controller/tareas/, así que las
 * carpetas antiguas están en controller/tareas/controller/tareas/documentos/ (solo modo único).
 */
function tarea_carpeta_fisica(string $carpeta): ?string
{
    foreach (almacen_raices() as $raiz) {
        $base = $raiz === SUBIDA_RAIZ ? SUBIDA_RAIZ . '/controller/tareas/' . TAREAS_RUTA_BD : $raiz . '/' . TAREAS_RUTA_BD;
        $dir = realpath($base . '/' . $carpeta);
        if ($dir !== false && is_dir($dir)) {
            return $dir;
        }
    }
    return null;
}

/** Dónde se crea la carpeta de una tarea nueva: en el almacén de la institución. */
function tarea_carpeta_nueva(string $carpeta): string
{
    return almacen_raiz() . '/' . TAREAS_RUTA_BD . '/' . $carpeta;
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
