<?php

/**
 * Cuentas del panel de superadministrador (Fase 4, hito 4.8). Solo por consola: el panel no crea cuentas.
 *
 *   php tools/crear_superadmin.php --usuario=ana --nombre="Ana Pérez"     (muestra la contraseña UNA vez)
 *   php tools/crear_superadmin.php --desactivar=ana                       (cierra también su sesión abierta)
 *
 * El panel se ve en https://SUPERADMIN_HOST/superadmin/ (modo múltiple).
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
use App\Superadmin\CuentasSuperadmin;

$opciones = getopt('', ['usuario:', 'nombre:', 'desactivar:']);
$valor = static fn (string $c): string => is_string($opciones[$c] ?? null) ? trim($opciones[$c]) : '';
$maestro = Conexion::maestro();
$cuentas = new CuentasSuperadmin($maestro);
$quien = 'consola:' . (get_current_user() ?: 'desconocido');

try {
    if ($valor('desactivar') !== '') {
        if (!$cuentas->desactivar($valor('desactivar'))) {
            throw new RuntimeException("No existe la cuenta «{$valor('desactivar')}».");
        }
        (new Auditoria($maestro))->registrar($quien, 'CUENTA_DESACTIVADA', null, $valor('desactivar'), '');
        echo "Cuenta «{$valor('desactivar')}» desactivada.\n";
        exit(0);
    }
    $clave = $cuentas->crear($valor('usuario'), $valor('nombre'));
    (new Auditoria($maestro))->registrar($quien, 'CUENTA_CREADA', null, $valor('usuario'), '');
    echo "Cuenta creada.\n  Usuario:     {$valor('usuario')}\n  Contraseña:  $clave\n"
        . "Se muestra solo ahora.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e instanceof PDOException && str_contains($e->getMessage(), 'Duplicate')
        ? "Ya existe la cuenta «{$valor('usuario')}».\n"
        : $e->getMessage() . "\n");
    exit(1);
}
