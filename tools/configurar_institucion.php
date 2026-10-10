<?php

/**
 * Configuración académica de una institución por consola (Fase 5.1); útil sobre todo en modo único, donde
 * no hay panel de superadministrador.
 *
 *   php tools/configurar_institucion.php                                   muestra la configuración
 *   php tools/configurar_institucion.php --tipo=INSTITUTO                  cambia el tipo (y sus valores por defecto)
 *   php tools/configurar_institucion.php --evaluacion.nota_minima=12       cambia una opción
 *   php tools/configurar_institucion.php --apoderado.obligatorio=          vacío = vuelve al valor por defecto
 *
 * En modo múltiple, --tenant=<slug>. Opciones: ver App\Institucion\Configuracion::CLAVES.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Core\Conexion;
use App\Institucion\Configuracion;
use App\Institucion\PdoConfiguracionRepositorio;
use App\Tenancy\ModoTenant;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\ResolverTenant;

$cambios = [];
$slug = null;
foreach (array_slice($argv, 1) as $argumento) {
    if (preg_match('/^--([a-z_.]+)=(.*)$/', $argumento, $m) !== 1) {
        fwrite(STDERR, "Opción no válida: $argumento\n");
        exit(2);
    }
    match ($m[1]) {
        'tenant' => $slug = $m[2],
        'tipo' => $cambios['institucion.tipo'] = $m[2],
        default => $cambios[$m[1]] = $m[2],
    };
}

$resolver = ResolverTenant::desdeConfig();
if ($resolver->modo() === ModoTenant::Unico) {
    $base = $resolver->resolver(null)->baseDatos;
} else {
    $tenant = $slug !== null ? (new PdoRepositorioTenants(Conexion::maestro()))->porSlug($slug) : null;
    if ($tenant === null) {
        fwrite(STDERR, "Modo múltiple: indica la institución con --tenant=<slug>.\n");
        exit(2);
    }
    $base = $tenant->baseDatos;
}

$repositorio = new PdoConfiguracionRepositorio(Conexion::administracion($base));
try {
    if ($cambios !== []) {
        $repositorio->guardar($cambios);
        if (isset($cambios['institucion.tipo']) && $slug !== null) {
            Conexion::maestro()->prepare('UPDATE tenants SET tipo = ? WHERE slug = ?')
                ->execute([Configuracion::validar('institucion.tipo', $cambios['institucion.tipo']), $slug]);
        }
    }
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$configuracion = $repositorio->cargar();
echo $configuracion->tipo()->etiqueta() . "\n";
foreach ($configuracion->todo() as $clave => $opcion) {
    printf("  %-24s %-14s %s\n", $clave, $opcion['valor'], $opcion['porDefecto'] ? '(por defecto)' : '');
}
