<?php

declare(strict_types=1);

/**
 * Módulos opcionales de la institución de la petición (facturación electrónica, asistente de IA).
 * Modo múltiple: los activa el superadministrador (BD maestra). Modo único: MODULOS_ACTIVOS en colegio.env.
 */

require_once __DIR__ . '/tenant.php';

use App\Comercial\ModuloOpcional;
use App\Comercial\ModulosInstitucion;
use App\Comercial\PdoRepositorioModulos;
use App\Core\Conexion;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

function modulos_institucion(): ModulosInstitucion
{
    static $modulos = null;
    if ($modulos === null) {
        $tenant = tenant_actual();
        try {
            $modulos = ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico
                ? ModulosInstitucion::desdeLista((string) config('MODULOS_ACTIVOS', ''), ($l = config('IA_LIMITE_MENSUAL')) !== null && $l !== '' ? (int) $l : null)
                : (new PdoRepositorioModulos(Conexion::maestro()))->de($tenant->slug);
        } catch (\PDOException $e) {
            // Sin la migración de la maestra todavía: ningún módulo opcional, nunca un error al entrar.
            error_log('Módulos opcionales sin BD maestra: ' . $e->getMessage());
            $modulos = new ModulosInstitucion();
        }
    }
    return $modulos;
}

function modulo_activo(ModuloOpcional $modulo): bool
{
    return modulos_institucion()->activo($modulo);
}

/** Para los controladores de un módulo opcional: 403 si la institución no lo tiene activo. */
function exigir_modulo(ModuloOpcional $modulo): void
{
    if (!modulo_activo($modulo)) {
        responder_error(403, "El módulo «{$modulo->etiqueta()}» no está activo para esta institución.");
    }
}
