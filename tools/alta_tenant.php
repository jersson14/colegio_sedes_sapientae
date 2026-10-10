<?php

/**
 * Alta de una institución en modo múltiple (Fase 4, hito 4.5).
 *
 * Crea su base, aplica las migraciones, siembra los roles y la empresa, crea el administrador y la
 * registra en la BD maestra. Muestra UNA vez la contraseña inicial del administrador (no se guarda en
 * claro en ningún sitio): entrégala por un canal seguro y pide que la cambie al entrar.
 *
 * Uso:
 *   php tools/alta_tenant.php --slug=colegio-x --razon="Colegio X" --email=direccion@colegiox.edu.pe \
 *       --admin-dni=12345678 --admin-nombres="Ana" --admin-apellidos="Pérez Soto" \
 *       [--admin-usuario=admin] [--tipo=COLEGIO|INSTITUTO|CETPRO] [--estado=PRUEBA|ACTIVO]
 *       [--dominio=intranet.colegiox.edu.pe] [--base=sge_colegio_x] [--demo]
 *
 * --demo (Fase 4B.6): con los datos de ejemplo anonimizados, para que el colegio pruebe el sistema; luego
 * se convierte en cliente con tools/convertir_demo.php (base limpia, mismo subdominio).
 *
 * Necesita un usuario de MySQL con permiso para crear bases (DB_MIGRACION_USER en colegio.env).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Core\Conexion;
use App\Tenancy\AltaInstitucion;
use App\Tenancy\EstadoTenant;
use App\Tenancy\MigradorPhinx;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;
use App\Tenancy\SolicitudAlta;

$opciones = getopt('', ['slug:', 'razon:', 'email:', 'admin-dni:', 'admin-nombres:', 'admin-apellidos:',
    'admin-usuario:', 'tipo:', 'estado:', 'dominio:', 'base:', 'demo']);
$valor = static fn (string $clave): string => is_string($opciones[$clave] ?? null) ? trim($opciones[$clave]) : '';

if (ResolverTenant::desdeConfig()->modo() !== ModoTenant::Multiple) {
    fwrite(STDERR, "El alta de instituciones es del modo múltiple (MODO_TENANT=multiple). En modo único, la\n"
        . "institución es la de colegio.env: ver docs/DESPLIEGUE.md §7.1.\n");
    exit(2);
}

try {
    $estado = EstadoTenant::tryFrom(strtoupper($valor('estado') ?: 'PRUEBA'));
    if ($estado === null || !$estado->permiteAcceso()) {
        throw new InvalidArgumentException('estado: PRUEBA, ACTIVO o MOROSO');
    }
    $solicitud = new SolicitudAlta(
        $valor('slug'),
        $valor('razon'),
        $valor('email'),
        $valor('admin-dni'),
        $valor('admin-nombres'),
        $valor('admin-apellidos'),
        $valor('admin-usuario') ?: 'admin',
        strtoupper($valor('tipo') ?: 'COLEGIO'),
        $estado,
        $valor('dominio') ?: null,
        $valor('base') ?: null,
        isset($opciones['demo']),
    );
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, $e->getMessage() . "\n\nUso: ver la cabecera de tools/alta_tenant.php\n");
    exit(2);
}

$alta = new AltaInstitucion(
    Conexion::administracion(),
    Conexion::maestro(),
    static fn (string $base): PDO => Conexion::administracion($base),
    static fn (string $base): bool => MigradorPhinx::ejecutar('migrate', $base),
);
try {
    echo "Creando «{$solicitud->slug}» en la base {$solicitud->baseDatos}…\n";
    $clave = $alta->ejecutar($solicitud);
} catch (Throwable $e) {
    fwrite(STDERR, "\nAlta cancelada (no quedó nada creado): " . $e->getMessage() . "\n");
    exit(1);
}

$dominio = (string) config('TENANT_DOMINIO', '');
echo "\nInstitución creada ({$solicitud->estado->value}).\n"
    . '  Dirección:      https://' . ($solicitud->dominio ?? "{$solicitud->slug}.$dominio") . "/\n"
    . "  Usuario:        {$solicitud->adminUsuario}\n"
    . "  Contraseña:     $clave\n"
    . "Se muestra solo ahora. Entrégala por un canal seguro y pide que la cambie al entrar.\n";
