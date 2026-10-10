<?php

/**
 * Tareas programadas por cron (Fase 4): reemplaza a los 4 eventos de la BD donde no hay
 * event_scheduler (hosting compartido) y, en modo múltiple, las ejecuta en cada institución.
 *
 *   - Cada ejecución: tareas vencidas → FINALIZADO, exámenes pasados → REALIZADO.
 *   - Una vez al año (31/12 23:59, recuperable hasta 7 días): alumnos → inactivos.
 *   - Modo múltiple, una vez al día: la foto del consumo de cada colegio (Fase 4B.4, para cobrar por alumno).
 *
 * Cron (cada minuto, como los eventos):   * * * * *  php /ruta/al/proyecto/tools/tareas_programadas.php
 * Un fallo en una institución no detiene a las demás; el código de salida es 1 si hubo alguno.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/autoload.php';

use App\Comercial\Consumo;
use App\Comercial\Facturacion;
use App\Core\Conexion;
use App\Services\TareasProgramadas;
use App\Tenancy\ModoTenant;
use App\Tenancy\PdoRepositorioTenants;
use App\Tenancy\ResolverTenant;
use App\Tenancy\TenantContext;

$resolver = ResolverTenant::desdeConfig();
$tenants = $resolver->modo() === ModoTenant::Unico
    ? [$resolver->resolver(null)]
    : array_values(array_filter(
        (new PdoRepositorioTenants(Conexion::maestro()))->todos(),
        static fn ($t): bool => $t->estado->permiteAcceso(),
    ));

// Fase 4B.4: en modo múltiple, una foto diaria del consumo de cada colegio (la primera pasada del día).
$facturacion = $resolver->modo() === ModoTenant::Multiple ? new Facturacion(Conexion::maestro()) : null;
$hoy = new DateTimeImmutable('today');
require_once __DIR__ . '/../core/subidas.php';

$fallos = 0;
foreach ($tenants as $tenant) {
    try {
        TenantContext::olvidar();
        TenantContext::establecer($tenant);
        $tareas = new TareasProgramadas(Conexion::crear());
        $cerradas = $tareas->cerrarVencidas();
        $cierre = $tareas->cierreDeAnio();
        if ($facturacion !== null && !$facturacion->tieneConsumo($tenant->slug, $hoy)) {
            $facturacion->registrarConsumo($tenant->slug, (new Consumo(Conexion::crear(), almacen_raiz()))->resumen(), $hoy);
        }
        // Solo se informa lo que cambió: el cron envía por correo cualquier salida.
        if ($cerradas['tareas'] + $cerradas['examenes'] > 0 || $cierre !== null) {
            echo date('Y-m-d H:i') . " {$tenant->slug}: {$cerradas['tareas']} tarea(s) y {$cerradas['examenes']} examen(es) cerrados"
                . ($cierre !== null ? "; cierre de año: $cierre alumno(s) inactivos" : '') . "\n";
        }
    } catch (\Throwable $e) {
        $fallos++;
        fwrite(STDERR, date('Y-m-d H:i') . " {$tenant->slug}: " . $e->getMessage() . "\n");
    }
}
exit($fallos === 0 ? 0 : 1);
