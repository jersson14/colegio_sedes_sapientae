<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\RepositorioTenants;
use App\Tenancy\Tenant;

final class RepositorioTenantsEnMemoria implements RepositorioTenants
{
    /** @var list<string> slugs y dominios por los que se preguntó */
    public array $consultados = [];

    /**
     * @param list<Tenant> $tenants
     * @param array<string, string> $dominios dominio propio => slug
     */
    public function __construct(private readonly array $tenants, private readonly array $dominios = [])
    {
    }

    public function porSlug(string $slug): ?Tenant
    {
        $this->consultados[] = $slug;
        foreach ($this->tenants as $tenant) {
            if ($tenant->slug === $slug) {
                return $tenant;
            }
        }
        return null;
    }

    public function porDominio(string $dominio): ?Tenant
    {
        $this->consultados[] = $dominio;
        return isset($this->dominios[$dominio]) ? $this->porSlug($this->dominios[$dominio]) : null;
    }

    public function todos(): array
    {
        return $this->tenants;
    }
}
