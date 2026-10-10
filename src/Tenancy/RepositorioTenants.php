<?php

declare(strict_types=1);

namespace App\Tenancy;

/** Registro de instituciones en la BD maestra. */
interface RepositorioTenants
{
    public function porSlug(string $slug): ?Tenant;

    /** Dominio propio de la institución (p. ej. intranet.colegio.edu.pe), además del subdominio. */
    public function porDominio(string $dominio): ?Tenant;

    /** @return list<Tenant> todos los que conservan sus datos, también los que no pueden entrar (migraciones, respaldos, cron). */
    public function todos(): array;
}
