<?php

/**
 * Cobros a las instituciones (Fase 4B.5), por cron una vez al día (modo múltiple):
 *
 *   0 6 * * *  php /ruta/tools/facturacion.php
 *
 * 1. Genera el cobro del periodo de cada suscripción vigente con precio (mensual o anual), con el consumo
 *    del último día medido (tools/tareas_programadas.php). No cobra a colegios en PRUEBA, SUSPENDIDO ni CANCELADO.
 * 2. Pasa a MOROSO a quien tenga un cobro vencido hace más de FACTURA_DIAS_GRACIA días (consulta, no da altas).
 *
 * Son cobros internos, no comprobantes electrónicos SUNAT (esos se emiten con un OSE/PSE).
 * Opción: --fecha=AAAA-MM-DD para simular otro día (pruebas).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Comercial\Facturacion;
use App\Core\Conexion;
use App\Superadmin\Auditoria;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

if (ResolverTenant::desdeConfig()->modo() !== ModoTenant::Multiple) {
    fwrite(STDERR, "La facturación es del modo múltiple: en modo único el cobro lo fija el contrato.\n");
    exit(2);
}
$fecha = null;
foreach (array_slice($argv, 1) as $argumento) {
    if (preg_match('/^--fecha=(\d{4}-\d{2}-\d{2})$/', $argumento, $m) === 1) {
        $fecha = $m[1];
    }
}
$hoy = new DateTimeImmutable($fecha ?? 'today');
$maestro = Conexion::maestro();
$facturacion = new Facturacion($maestro, (int) config('FACTURA_DIAS_PAGO', '10'), (int) config('FACTURA_DIAS_GRACIA', '5'));
$auditoria = new Auditoria($maestro);

foreach ($facturacion->generar($hoy) as $numero) {
    $auditoria->registrar('cron:facturacion', 'COBRO_EMITIDO', null, $numero, '');
    echo "Cobro emitido: $numero\n";
}
foreach ($facturacion->marcarMorosos($hoy) as $slug) {
    $auditoria->registrar('cron:facturacion', 'MOROSO_AUTOMATICO', $slug, 'cobro vencido fuera del plazo de gracia', '');
    echo "Pasa a MOROSO: $slug\n";
}
