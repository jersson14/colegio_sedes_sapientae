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
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\ResolverTenant;

$accion = in_array($argv[1] ?? 'migrate', ['migrate', 'status'], true) ? ($argv[1] ?? 'migrate') : null;
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

/** Ejecuta Phinx en un proceso aparte con las credenciales por variables de entorno (sin argumentos visibles). */
function phinx(string $accion, string $configuracion, string $base, string $etiqueta): bool
{
    $usuario = (string) config('DB_MIGRACION_USER', '');
    $entorno = getenv() + [
        'DB_HOST' => (string) config('DB_HOST', 'localhost'),
        'DB_PORT' => (string) config('DB_PORT', '3306'),
        'DB_NAME' => $base,
        'MAESTRO_DB_NAME' => $base,
        'DB_MIGRACION_USER' => $usuario !== '' ? $usuario : (string) config('DB_USER', ''),
        'DB_MIGRACION_PASS' => $usuario !== '' ? (string) config('DB_MIGRACION_PASS', '') : (string) config('DB_PASS', ''),
    ];
    $comando = [PHP_BINARY, __DIR__ . '/../vendor/robmorgan/phinx/bin/phinx', $accion, '-c', $configuracion, '--no-interaction'];
    echo "== $etiqueta ($base)\n";
    $proceso = proc_open($comando, [1 => STDOUT, 2 => STDERR], $tuberias, __DIR__ . '/..', $entorno);
    return is_resource($proceso) && proc_close($proceso) === 0;
}

$raiz = __DIR__ . '/..';
if (ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico) {
    exit(phinx($accion, "$raiz/phinx.php", (string) config('DB_NAME', 'colegio'), 'institución única') ? 0 : 1);
}

if ($solo === null && !phinx($accion, "$raiz/phinx_maestro.php", (string) config('MAESTRO_DB_NAME', 'sge_maestro'), 'BD maestra')) {
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
    if (!phinx($accion, "$raiz/phinx.php", $tenant->baseDatos, $tenant->slug . ' [' . $tenant->estado->value . ']')) {
        $pendientes = array_map(static fn ($t): string => $t->slug, array_slice($tenants, $i + 1));
        fwrite(STDERR, "Falló «{$tenant->slug}». Sin migrar todavía: " . ($pendientes === [] ? 'ninguna' : implode(', ', $pendientes)) . "\n");
        exit(1);
    }
}
echo count($tenants) . " institución(es) al día.\n";
