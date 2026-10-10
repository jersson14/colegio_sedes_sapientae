<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
require_once __DIR__ . '/../../core/subidas.php';

/**
 * Fotos y logos subidos (Fase 4.6). La BD guarda «controller/<módulo>/fotos/<archivo>» y el panel la
 * pide tal cual; .htaccess (o tests/E2E/router.php con el servidor de PHP) envía aquí esas URLs cuando
 * no son un archivo físico, es decir, todo lo subido desde la Fase 4: está en el almacén de la
 * institución, fuera del alcance de la URL, y solo lo ve un usuario con sesión EN esa institución.
 *
 * GET ?ruta=controller/alumnos/fotos/IMG….jpg
 */

/** Carpetas de imágenes que existen en el sistema (la ruta pedida debe estar en una de ellas). */
const CARPETAS_IMAGENES = [
    'controller/alumnos/fotos',
    'controller/docentes/fotos',
    'controller/personal_administrativo/fotos',
    'controller/comunicados/fotos',
    'controller/empleado/FOTOS',
    'controller/empresa/FOTOS',
];

$ruta = (string) ($_GET['ruta'] ?? '');
$carpeta = dirname($ruta);
if (!in_array($carpeta, CARPETAS_IMAGENES, true) || preg_match('/^[A-Za-z0-9._-]{1,150}$/', basename($ruta)) !== 1) {
    responder_error(404, 'Archivo no encontrado');
}
$archivo = subida_ubicar($ruta, $carpeta);
$mime = $archivo !== null ? (new finfo(FILEINFO_MIME_TYPE))->file($archivo) : false;
if ($archivo === null || !isset(IMAGEN_TIPOS[$mime])) {
    responder_error(404, 'Archivo no encontrado');
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($archivo));
// Datos de menores: el navegador puede guardarla, los intermediarios no.
header('Cache-Control: private, max-age=3600');
readfile($archivo);
