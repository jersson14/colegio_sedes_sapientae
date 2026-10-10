<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_conexion.php';
require_once __DIR__ . '/../../core/subidas.php';

use App\Comercial\ExportacionDatos;
use App\Core\Conexion;
use App\Superadmin\Auditoria;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

/**
 * Descarga de todos los datos de la institución (Fase 4B.7): un zip con un CSV por tabla y sus archivos.
 * Es lo único que puede pedir una institución suspendida (core/guard.php → Restricciones::EXPORTACION).
 */

$tenant = tenant_actual();
$unico = ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico;
$pdo = Conexion::crear();
$nombre = (string) $pdo->query('SELECT emp_razon FROM empresa ORDER BY empresa_id LIMIT 1')->fetchColumn();
$zip = (string) tempnam(sys_get_temp_dir(), 'exp');
try {
    $resumen = (new ExportacionDatos())->generar(
        $pdo,
        $nombre !== '' ? $nombre : $tenant->slug,
        almacen_raiz(),
        $zip,
        $unico ? subidas_anteriores() : [],
        subida_es_de_la_aplicacion(...),
    );
    if (!$unico) {
        (new Auditoria(Conexion::maestro()))->registrar('admin:' . ($_SESSION['S_USU'] ?? '?'), 'EXPORTACION', $tenant->slug,
            "{$resumen['tablas']} tablas, {$resumen['filas']} filas, {$resumen['archivos']} archivos", (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="datos-' . $tenant->slug . '-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . (string) filesize($zip));
    header('Cache-Control: no-store');
    readfile($zip);
} finally {
    @unlink($zip);
}

