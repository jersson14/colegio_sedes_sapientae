<?php

declare(strict_types=1);

/**
 * Tenant de la petición (Fase 4, hitos 4.2 y 4.3). Lo usan la sesión y las dos conexiones
 * (model/model_conexion.php y view/MPDF/conexion.php): ninguna abre la BD sin pasar por aquí.
 *
 * - Modo único (MODO_TENANT=unico, por defecto): la institución de colegio.env.
 * - Modo múltiple: el subdominio o dominio propio del Host. Host desconocido → 404 sin detalles.
 *   En línea de comandos no hay Host: la herramienta elige el tenant con TenantContext::establecer().
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;
use App\Tenancy\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantNoEncontrado;
use App\Tenancy\TenantNoResuelto;

function tenant_actual(): Tenant
{
    if (TenantContext::resuelto()) {
        return TenantContext::actual();
    }
    $resolver = ResolverTenant::desdeConfig();
    if ($resolver->modo() === ModoTenant::Multiple && PHP_SAPI === 'cli') {
        throw new TenantNoResuelto('Modo múltiple en línea de comandos: elige el tenant con TenantContext::establecer().');
    }
    try {
        TenantContext::establecer($resolver->resolver($_SERVER['HTTP_HOST'] ?? null));
    } catch (TenantNoEncontrado) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Institución no encontrada.');
    } catch (PDOException $e) {
        // La BD maestra no responde: el detalle al log, nunca al cliente.
        error_log('BD maestra no disponible: ' . $e->getMessage());
        http_response_code(500);
        exit('Error de conexión con la base de datos.');
    }
    return TenantContext::actual();
}
