<?php

/**
 * Migraciones en todas las bases (Fase 4, hito 4.4).
 *
 * Modo único: equivale a `vendor/bin/phinx migrate` sobre DB_NAME.
 * Modo múltiple: migra primero la BD maestra y después la base de CADA institución registrada,
 * en orden. Se detiene en el primer fallo: con el mismo código, dos colegios en versiones de
 * esquema distintas son un riesgo mayor que uno sin actualizar (se corrige y se vuelve a lanzar;
 * las bases ya migradas no repiten nada).
 *
 * Uso:  php tools/migrar_tenants.php [migrate|status] [--solo=<slug>]
 * Credenciales con DDL: DB_MIGRACION_USER/DB_MIGRACION_PASS de colegio.env (o DB_USER/DB_PASS).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Core\Conexion;
use App\Tenancy\ModoTenant;
use App\Tenancy\MigradorPhinx;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\ResolverTenant;

// La acción es opcional también cuando solo se pasan opciones (php tools/migrar_tenants.php --solo=x).
$primero = $argv[1] ?? 'migrate';
$primero = str_starts_with($primero, '--') ? 'migrate' : $primero;
$accion = in_array($primero, ['migrate', 'status'], true) ? $primero : null;
if ($accion === null) {
    fwrite(STDERR, "Uso: php tools/migrar_tenants.php [migrate|status] [--solo=<slug>]\n");
    exit(2);
}
$solo = null;
foreach ($argv as $argumento) {
    if (str_starts_with($argumento, '--solo=')) {
        $solo = substr($argumento, 7);
    }
}

function phinx(string $accion, string $base, string $etiqueta, bool $maestra = false): bool
{
    echo "== $etiqueta ($base)\n";
    return MigradorPhinx::ejecutar($accion, $base, $maestra);
}

if (ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico) {
    exit(phinx($accion, (string) config('DB_NAME', 'colegio'), 'institución única') ? 0 : 1);
}

if ($solo === null && !phinx($accion, (string) config('MAESTRO_DB_NAME', 'sge_maestro'), 'BD maestra', true)) {
    fwrite(STDERR, "Falló la BD maestra: no se toca ninguna institución.\n");
    exit(1);
}
$tenants = (new PdoRepositorioTenants(Conexion::maestro()))->todos();
if ($solo !== null) {
    $tenants = array_values(array_filter($tenants, static fn ($t): bool => $t->slug === $solo));
    if ($tenants === []) {
        fwrite(STDERR, "No hay ninguna institución «{$solo}».\n");
        exit(2);
    }
}
foreach ($tenants as $i => $tenant) {
    if (!phinx($accion, $tenant->baseDatos, $tenant->slug . ' [' . $tenant->estado->value . ']')) {
        $pendientes = array_map(static fn ($t): string => $t->slug, array_slice($tenants, $i + 1));
        fwrite(STDERR, "Falló «{$tenant->slug}». Sin migrar todavía: " . ($pendientes === [] ? 'ninguna' : implode(', ', $pendientes)) . "\n");
        exit(1);
    }
}
echo count($tenants) . " institución(es) al día.\n";
