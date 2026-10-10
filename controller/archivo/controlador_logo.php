<?php

declare(strict_types=1);

/**
 * Logo de la institución del host (Fase 4.7). PÚBLICO: se ve en la página de acceso, antes de la sesión.
 * No recibe ninguna ruta: sirve solo el logo que la empresa de ESTA institución tiene guardado, y solo
 * si está en su almacén. Cualquier otro caso → la marca neutra.
 */

require_once __DIR__ . '/../../core/tenant.php';
require_once __DIR__ . '/../../core/subidas.php';
require_once __DIR__ . '/../../model/model_conexion.php';

use App\Core\Conexion;
use App\Tenancy\Marca;

tenant_actual();
$archivo = null;
try {
    $ruta = (string) Conexion::crear()->query('SELECT emp_logo FROM empresa ORDER BY empresa_id LIMIT 1')->fetchColumn();
    $archivo = $ruta !== '' ? subida_ubicar($ruta, dirname($ruta)) : null;
} catch (PDOException $e) {
    error_log('Logo sin BD: ' . $e->getMessage());
}
$mime = $archivo !== null ? (new finfo(FILEINFO_MIME_TYPE))->file($archivo) : false;
if ($archivo === null || !isset(IMAGEN_TIPOS[$mime])) {
    $archivo = __DIR__ . '/../../' . Marca::NEUTRA;
    $mime = 'image/svg+xml';
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($archivo));
header('Cache-Control: public, max-age=300');
readfile($archivo);
