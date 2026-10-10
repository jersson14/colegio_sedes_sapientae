<?php

/**
 * Mueve al almacén de la institución (Fase 4.6) los archivos subidos antes de la Fase 4, que siguen en
 * controller/<módulo>/fotos/ y controller/tareas/controller/tareas/documentos/. Las rutas de la BD no
 * cambian: después del traslado se sirven igual, ahora con sesión y desde fuera del alcance de la URL.
 *
 * Necesario antes de llevar una instalación de modo único a un servidor en modo múltiple (allí la
 * carpeta común nunca se consulta). En modo único es opcional.
 *
 * Por defecto solo INFORMA. Con --aplicar mueve (sin sobrescribir nada que ya exista en el almacén).
 * Uso:  php tools/mover_subidas.php [--aplicar] [--tenant=<slug>]   (en modo múltiple, --tenant es obligatorio)
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
use App\Tenancy\TenantContext;

$aplicar = in_array('--aplicar', $argv, true);
$slug = null;
foreach ($argv as $argumento) {
    if (str_starts_with($argumento, '--tenant=')) {
        $slug = substr($argumento, 9);
    }
}
$resolver = ResolverTenant::desdeConfig();
if ($resolver->modo() === ModoTenant::Unico) {
    TenantContext::establecer($resolver->resolver(null));
} else {
    $tenant = $slug !== null ? (new PdoRepositorioTenants(Conexion::maestro()))->porSlug($slug) : null;
    if ($tenant === null) {
        fwrite(STDERR, "Modo múltiple: indica la institución dueña de los archivos con --tenant=<slug>.\n");
        exit(2);
    }
    TenantContext::establecer($tenant);
}
require __DIR__ . '/../core/subidas.php';

$destino = almacen_raiz();
$origenes = subidas_anteriores();

$movidos = 0;
$omitidos = 0;
foreach ($origenes as [$origen, $rutaBd]) {
    if (!is_dir($origen)) {
        continue;
    }
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origen, FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        /** @var SplFileInfo $archivo */
        $nombre = $archivo->getFilename();
        // Lo que es de la aplicación y no de los usuarios se queda.
        if ($archivo->isDir() || subida_es_de_la_aplicacion($nombre)) {
            continue;
        }
        $relativa = str_replace('\\', '/', substr($archivo->getPathname(), strlen($origen) + 1));
        $final = "$destino/$rutaBd/$relativa";
        if (file_exists($final)) {
            $omitidos++;
            echo "YA EXISTE  $rutaBd/$relativa\n";
            continue;
        }
        echo ($aplicar ? 'MOVIDO     ' : 'SE MOVERÍA ') . "$rutaBd/$relativa\n";
        if ($aplicar) {
            if (!is_dir(dirname($final))) {
                mkdir(dirname($final), 0755, true);
            }
            if (!rename($archivo->getPathname(), $final)) {
                fwrite(STDERR, "No se pudo mover {$archivo->getPathname()}\n");
                exit(1);
            }
        }
        $movidos++;
    }
}
echo "\n$movidos archivo(s) " . ($aplicar ? 'movidos' : 'por mover') . " a $destino; $omitidos ya estaban.\n";
if (!$aplicar && $movidos > 0) {
    echo "Haz una copia de seguridad y repite con --aplicar.\n";
}
