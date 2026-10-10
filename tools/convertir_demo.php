<?php

/**
 * Convierte una demo en cliente (Fase 4B.6): misma dirección, base limpia, administrador real.
 * Los datos de ejemplo de la demo se borran.
 *
 *   php tools/convertir_demo.php --tenant=colegio-x --razon="Colegio X" --email=direccion@colegiox.edu.pe \
 *       --admin-dni=12345678 --admin-nombres="Ana" --admin-apellidos="Pérez Soto" [--admin-usuario=admin] [--estado=ACTIVO]
 *
 * Muestra UNA vez la contraseña del administrador. La demo se crea con: php tools/alta_tenant.php ... --demo
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Core\Conexion;
use App\Superadmin\Auditoria;
use App\Tenancy\AltaInstitucion;
use App\Tenancy\ConversionDemo;
use App\Tenancy\EstadoTenant;
use App\Tenancy\MigradorPhinx;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;
use App\Tenancy\SolicitudAlta;

$opciones = getopt('', ['tenant:', 'razon:', 'email:', 'admin-dni:', 'admin-nombres:', 'admin-apellidos:', 'admin-usuario:', 'estado:']);
$valor = static fn (string $c): string => is_string($opciones[$c] ?? null) ? trim($opciones[$c]) : '';
if (ResolverTenant::desdeConfig()->modo() !== ModoTenant::Multiple) {
    fwrite(STDERR, "Las demos son del modo múltiple.\n");
    exit(2);
}

try {
    $slug = $valor('tenant');
    $datos = new SolicitudAlta(
        $slug,
        $valor('razon'),
        $valor('email'),
        $valor('admin-dni'),
        $valor('admin-nombres'),
        $valor('admin-apellidos'),
        $valor('admin-usuario') ?: 'admin',
        'COLEGIO',
        EstadoTenant::tryFrom(strtoupper($valor('estado') ?: 'ACTIVO')) ?? EstadoTenant::Activo,
        null,
        ConversionDemo::baseCliente($slug),
    );
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, $e->getMessage() . "\n\nUso: ver la cabecera de tools/convertir_demo.php\n");
    exit(2);
}

$maestro = Conexion::maestro();
$alta = new AltaInstitucion(
    Conexion::administracion(),
    $maestro,
    static fn (string $base): PDO => Conexion::administracion($base),
    static fn (string $base): bool => MigradorPhinx::ejecutar('migrate', $base),
);
try {
    $resultado = (new ConversionDemo(Conexion::administracion(), $maestro, $alta, rtrim(config('ALMACEN_DIR') ?: dirname(__DIR__) . '/storage/tenants', '/\\')))
        ->convertir($datos);
} catch (Throwable $e) {
    fwrite(STDERR, "\nNo se convirtió (la demo sigue igual): " . $e->getMessage() . "\n");
    exit(1);
}
(new Auditoria($maestro))->registrar('consola:' . (get_current_user() ?: 'desconocido'), 'DEMO_CONVERTIDA', $slug, "base {$resultado['base']}", '');
echo "\n«{$slug}» es ya cliente ({$datos->estado->value}), con una base limpia ({$resultado['base']}).\n"
    . "  Usuario:     {$datos->adminUsuario}\n  Contraseña:  {$resultado['clave']}\nSe muestra solo ahora.\n";
