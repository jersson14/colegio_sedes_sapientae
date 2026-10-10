<?php

/**
 * Copia de seguridad y restauración por institución (Fase 4, hito 4.10).
 *
 *   php tools/respaldo_tenant.php respaldar [--tenant=<slug> | --todos] [--destino=<carpeta>]
 *   php tools/respaldo_tenant.php verificar --desde=<carpeta del respaldo>
 *   php tools/respaldo_tenant.php restaurar --desde=<carpeta del respaldo> [--activar]
 *
 * - respaldar: base (sin DEFINER) + almacén de archivos + manifiesto con sumas y filas por tabla, en
 *   RESPALDO_DIR (por defecto <carpeta de colegio.env>/respaldos, fuera del docroot).
 * - verificar: comprueba las sumas sin tocar nada.
 * - restaurar: SIEMPRE en una base nueva, y comprueba que cada tabla tenga las filas del respaldo; los
 *   archivos quedan en <almacén>/<slug>.restaurado-<fecha>. Sin --activar, el colegio sigue igual (para
 *   revisar o rescatar datos). Con --activar (modo múltiple), pasa a usar la base y los archivos
 *   restaurados; los anteriores NO se borran (se indican para retirarlos después de comprobar).
 *
 * Necesita mysqldump y mysql (MYSQL_BIN_DIR si no están en el PATH) y el usuario de migraciones.
 * Cron diario sugerido:  0 2 * * *  php /ruta/tools/respaldo_tenant.php respaldar --todos
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

require __DIR__ . '/../core/tenant.php';
require __DIR__ . '/../core/subidas.php';

use App\Core\Conexion;
use App\Tenancy\ClienteMysql;
use App\Tenancy\ModoTenant;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\RespaldoInstitucion;
use App\Tenancy\ResolverTenant;
use App\Tenancy\Tenant;
use App\Tenancy\TenantContext;

$accion = $argv[1] ?? '';
// getopt() deja de leer en la primera palabra que no es opción (la acción): se leen a mano.
$opciones = [];
foreach (array_slice($argv, 2) as $argumento) {
    if (preg_match('/^--(tenant|destino|desde)=(.*)$/', $argumento, $m) === 1) {
        $opciones[$m[1]] = $m[2];
    } elseif (in_array($argumento, ['--todos', '--activar'], true)) {
        $opciones[substr($argumento, 2)] = true;
    } else {
        fwrite(STDERR, "Opción desconocida: $argumento\n");
        exit(2);
    }
}
$opcion = static fn (string $clave): string => is_string($opciones[$clave] ?? null) ? $opciones[$clave] : '';
if (!in_array($accion, ['respaldar', 'verificar', 'restaurar'], true)) {
    fwrite(STDERR, "Uso: ver la cabecera de tools/respaldo_tenant.php\n");
    exit(2);
}

$resolver = ResolverTenant::desdeConfig();
$multiple = $resolver->modo() === ModoTenant::Multiple;
$registro = $multiple ? new PdoRepositorioTenants(Conexion::maestro()) : null;
$almacenBase = rtrim(config('ALMACEN_DIR') ?: dirname(__DIR__) . '/storage/tenants', '/\\');
$servicio = new RespaldoInstitucion(
    Conexion::administracion(),
    static fn (string $base): PDO => Conexion::administracion($base),
    ClienteMysql::desdeConfig(),
);

try {
    if ($accion === 'respaldar') {
        $destino = $opcion('destino') ?: (config('RESPALDO_DIR') ?: dirname(config_ruta_env()) . '/respaldos');
        if ($registro === null) {
            $tenants = [$resolver->resolver(null)];
        } elseif (isset($opciones['todos'])) {
            $tenants = $registro->todos();
        } else {
            $uno = $registro->porSlug($opcion('tenant'));
            $tenants = $uno !== null ? [$uno] : throw new InvalidArgumentException('Modo múltiple: indica --tenant=<slug> o --todos.');
        }
        $fallos = 0;
        foreach ($tenants as $tenant) {
            try {
                // Modo único: también lo subido antes de la Fase 4, que sigue en su carpeta de entonces.
                $carpeta = $multiple
                    ? $servicio->respaldar($tenant, "$almacenBase/{$tenant->slug}", $destino)
                    : $servicio->respaldar($tenant, "$almacenBase/{$tenant->slug}", $destino, subidas_anteriores(), subida_es_de_la_aplicacion(...));
                echo "{$tenant->slug}: $carpeta\n";
            } catch (Throwable $e) {
                $fallos++;
                fwrite(STDERR, "{$tenant->slug}: FALLÓ — {$e->getMessage()}\n");
            }
        }
        exit($fallos === 0 ? 0 : 1);
    }

    $desde = rtrim($opcion('desde'), '/\\');
    if ($desde === '') {
        throw new InvalidArgumentException('Indica la carpeta del respaldo con --desde=<carpeta>.');
    }
    $manifiesto = RespaldoInstitucion::manifiesto($desde);
    if ($accion === 'verificar') {
        echo "Respaldo íntegro: {$manifiesto['slug']} del {$manifiesto['creado']}, " . array_sum((array) $manifiesto['filas'])
            . " filas, {$manifiesto['archivos_almacen']} archivos, migración {$manifiesto['migracion']}.\n";
        exit(0);
    }

    $slug = (string) $manifiesto['slug'];
    $actual = $registro?->porSlug($slug);
    if ($multiple && $actual === null) {
        throw new RuntimeException("La institución «{$slug}» no está registrada: date de alta primero o registra la fila.");
    }
    if (!$multiple && $slug !== $resolver->resolver(null)->slug) {
        throw new RuntimeException("El respaldo es de «{$slug}» y esta instalación es «{$resolver->resolver(null)->slug}».");
    }
    $marca = date('Ymd-His');
    $resultado = $servicio->restaurar($desde, "$almacenBase/$slug.restaurado-$marca");
    echo "Restaurado y verificado: base {$resultado['base']} ({$resultado['filas']} filas), "
        . "{$resultado['archivos']} archivos en $almacenBase/$slug.restaurado-$marca\n";

    if (!isset($opciones['activar'])) {
        echo "El colegio sigue usando su base actual. Para pasar a la restaurada, repite con --activar.\n";
        exit(0);
    }
    if ($actual === null) { // modo único: no hay registro que cambiar
        echo "Modo único: para usarla, cambia DB_NAME={$resultado['base']} en colegio.env y renombra la carpeta\n"
            . "de archivos restaurada a $almacenBase/$slug (guarda antes la actual).\n";
        exit(0);
    }
    // Activar: el registro apunta a la base restaurada y los archivos se intercambian; nada se borra.
    $anterior = "$almacenBase/$slug.anterior-$marca";
    if (is_dir("$almacenBase/$slug") && !rename("$almacenBase/$slug", $anterior)) {
        throw new RuntimeException("No se pudo apartar $almacenBase/$slug");
    }
    if (!rename("$almacenBase/$slug.restaurado-$marca", "$almacenBase/$slug")) {
        throw new RuntimeException('No se pudieron activar los archivos restaurados.');
    }
    Conexion::maestro()->prepare('UPDATE tenants SET base_datos = ? WHERE slug = ?')->execute([$resultado['base'], $slug]);
    TenantContext::olvidar();
    echo "Activado: «{$slug}» usa ya la base {$resultado['base']}.\n"
        . "Se conservan, para retirarlos cuando lo compruebes: la base {$actual->baseDatos} y $anterior\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se completó: {$e->getMessage()}\n");
    exit(1);
}
