<?php

/**
 * Baja de una institución (Fase 4B.8), solo por consola y en modo múltiple:
 *
 *   php tools/baja_tenant.php exportar --tenant=<slug>          exportación final para entregar (queda auditada)
 *   php tools/baja_tenant.php purgar --tenant=<slug>            muestra qué se borraría y si se puede
 *   php tools/baja_tenant.php purgar --tenant=<slug> --confirmar=<slug>   borra, verifica y deja constancia
 *
 * Solo se borra una institución CANCELADA (en el panel), con la retención pactada vencida y con exportación
 * final. Se borran su base, su almacén y sus respaldos; la constancia queda en RESPALDO_DIR/bajas/.
 * NO tiene vuelta atrás: haz antes una copia externa si el contrato lo pide.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Comercial\BajaInstitucion;
use App\Core\Conexion;
use App\Superadmin\Auditoria;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

$accion = $argv[1] ?? '';
$opciones = [];
foreach (array_slice($argv, 2) as $argumento) {
    if (preg_match('/^--(tenant|confirmar)=(.*)$/', $argumento, $m) === 1) {
        $opciones[$m[1]] = $m[2];
    }
}
$slug = $opciones['tenant'] ?? '';
if (!in_array($accion, ['exportar', 'purgar'], true) || $slug === '') {
    fwrite(STDERR, "Uso: ver la cabecera de tools/baja_tenant.php\n");
    exit(2);
}
if (ResolverTenant::desdeConfig()->modo() !== ModoTenant::Multiple) {
    fwrite(STDERR, "La baja de instituciones es del modo múltiple.\n");
    exit(2);
}

$maestro = Conexion::maestro();
$baja = new BajaInstitucion(
    $maestro,
    Conexion::administracion(),
    new Auditoria($maestro),
    rtrim(config('ALMACEN_DIR') ?: dirname(__DIR__) . '/storage/tenants', '/\\'),
    rtrim(config('RESPALDO_DIR') ?: dirname(config_ruta_env()) . '/respaldos', '/\\'),
);
$actor = 'consola:' . (get_current_user() ?: 'desconocido');

try {
    if ($accion === 'exportar') {
        $base = (string) $maestro->query('SELECT base_datos FROM tenants WHERE slug = ' . $maestro->quote($slug))->fetchColumn();
        if ($base === '') {
            throw new DomainException("No existe la institución «{$slug}».");
        }
        $resultado = $baja->exportarFinal($slug, Conexion::administracion($base), $actor);
        echo "Exportación final: {$resultado['archivo']}\nSHA-256: {$resultado['sha256']}\n"
            . "Entrégala a la institución por un canal seguro (contiene datos personales de menores).\n";
        exit(0);
    }

    $hoy = new DateTimeImmutable('today');
    if (!isset($opciones['confirmar'])) {
        $plan = $baja->plan($slug, $hoy);
        echo "Se borraría de «{$slug}»:\n  - la base {$plan['base']}\n  - " . ($plan['almacen'] ?? 'sin almacén de archivos')
            . "\n  - " . count($plan['respaldos']) . " respaldo(s)\n";
        echo $plan['impedimentos'] === []
            ? "Se puede borrar. Repite con --confirmar={$slug}\n"
            : "No se puede borrar todavía:\n  - " . implode("\n  - ", $plan['impedimentos']) . "\n";
        exit($plan['impedimentos'] === [] ? 0 : 1);
    }
    $constancia = $baja->purgar($slug, $opciones['confirmar'], $hoy, $actor);
    echo "Borrado y verificado. Constancia: $constancia\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
