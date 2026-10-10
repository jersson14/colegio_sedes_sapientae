<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Core\Conexion;

/**
 * Decide la institución de una petición a partir del host.
 *
 * - Modo único: siempre la misma (la de colegio.env); el host no importa.
 * - Modo múltiple: «<slug>.<TENANT_DOMINIO>» → BD maestra; si el host no es un subdominio, se busca
 *   como dominio propio. Un host desconocido, suspendido o cancelado da el mismo TenantNoEncontrado:
 *   la respuesta no revela qué instituciones existen.
 */
final class ResolverTenant
{
    /** @param (\Closure(): RepositorioTenants)|null $repositorio perezoso: el modo único no abre la maestra */
    public function __construct(
        private readonly ModoTenant $modo,
        private readonly ?Tenant $tenantUnico = null,
        private readonly ?\Closure $repositorio = null,
        private readonly string $dominioBase = '',
        private readonly int $diasExportacion = 30,
    ) {
        if ($modo === ModoTenant::Unico && $tenantUnico === null) {
            throw new \InvalidArgumentException('El modo único necesita su tenant.');
        }
        if ($modo === ModoTenant::Multiple && $repositorio === null) {
            throw new \InvalidArgumentException('El modo múltiple necesita el registro de tenants.');
        }
    }

    public static function desdeConfig(): self
    {
        require_once __DIR__ . '/../../core/config.php';
        $modo = ModoTenant::desdeConfig(config('MODO_TENANT'));
        if ($modo === ModoTenant::Unico) {
            return new self($modo, new Tenant(
                (string) config('TENANT_SLUG', 'principal'),
                (string) config('DB_NAME', 'colegio'),
                EstadoTenant::Activo,
                (string) config('TENANT_RAZON_SOCIAL', ''),
            ));
        }
        return new self(
            $modo,
            repositorio: static fn (): RepositorioTenants => new PdoRepositorioTenants(Conexion::maestro()),
            dominioBase: (string) config('TENANT_DOMINIO', ''),
            diasExportacion: (int) config('SUSPENSION_DIAS_EXPORTACION', '30'),
        );
    }

    public function modo(): ModoTenant
    {
        return $this->modo;
    }

    /** @throws TenantNoEncontrado */
    public function resolver(?string $host): Tenant
    {
        if ($this->modo === ModoTenant::Unico) {
            return $this->tenantUnico ?? throw new \LogicException('Sin tenant único.');
        }
        $host = self::normalizarHost((string) $host);
        if ($host === null) {
            throw new TenantNoEncontrado('Host inválido.');
        }
        $repositorio = ($this->repositorio ?? throw new \LogicException('Sin registro de tenants.'))();
        $slug = self::slugDesdeHost($host, $this->dominioBase);
        $tenant = $slug !== null ? $repositorio->porSlug($slug) : $repositorio->porDominio($host);
        if ($tenant === null || !$this->puedeEntrar($tenant)) {
            throw new TenantNoEncontrado('Institución no disponible.');
        }
        return $tenant;
    }

    /**
     * PRUEBA, ACTIVO y MOROSO entran. SUSPENDIDO, solo durante la ventana de exportación (Fase 4B.3: su
     * administrador descarga sus datos); pasada, o sin fecha de suspensión, como CANCELADO: no existe.
     */
    private function puedeEntrar(Tenant $tenant): bool
    {
        if ($tenant->estado->permiteAcceso()) {
            return true;
        }
        return $tenant->estado === EstadoTenant::Suspendido && $tenant->suspendidoDesde !== null
            && $tenant->suspendidoDesde > new \DateTimeImmutable("-{$this->diasExportacion} days");
    }

    /** Minúsculas, sin puerto ni punto final; null si no es un nombre de host. */
    public static function normalizarHost(string $host): ?string
    {
        $host = (string) preg_replace('/:\d{1,5}$/', '', strtolower(trim($host, " \t")));
        $host = rtrim($host, '.');
        return preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/', $host) === 1 && !str_contains($host, '..')
            ? $host
            : null;
    }

    /** «sapientae.miapp.pe» con base «miapp.pe» → «sapientae». Un solo nivel: «a.b.miapp.pe» no es tenant. */
    public static function slugDesdeHost(string $host, string $dominioBase): ?string
    {
        $base = self::normalizarHost($dominioBase);
        if ($base === null || !str_ends_with($host, '.' . $base)) {
            return null;
        }
        $slug = substr($host, 0, -strlen('.' . $base));
        return Tenant::slugValido($slug) ? $slug : null;
    }
}
