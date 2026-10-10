<?php

declare(strict_types=1);

/**
 * Estado y plan de la institución de la petición (Fase 4B.2 y 4B.3), para el guard, las subidas, el panel
 * y los PDF. En modo único no hay restricciones comerciales (instancia propia o dedicada).
 */

require_once __DIR__ . '/tenant.php';

use App\Comercial\Condiciones;
use App\Comercial\Consumo;
use App\Comercial\PdoRepositorioComercial;
use App\Comercial\Recurso;
use App\Comercial\Restricciones;
use App\Core\Conexion;
use App\Tenancy\ModoTenant;
use App\Tenancy\ResolverTenant;

function comercial_condiciones(): Condiciones
{
    static $condiciones = null;
    if ($condiciones === null) {
        $tenant = tenant_actual();
        $condiciones = ResolverTenant::desdeConfig()->modo() === ModoTenant::Unico
            ? Condiciones::sinRestricciones()
            : ((new PdoRepositorioComercial(Conexion::maestro(), (int) config('SUSPENSION_DIAS_EXPORTACION', '30')))->condiciones($tenant->slug)
                ?? new Condiciones($tenant->estado));
    }
    return $condiciones;
}

function comercial_consumo(): Consumo
{
    require_once __DIR__ . '/subidas.php';
    return new Consumo(Conexion::crear(), almacen_raiz());
}

/** Lo llama core/guard.php con el script pedido: corta con 402/403 si el estado o el plan no lo permiten. */
function comercial_verificar_peticion(string $script): void
{
    $raiz = (string) realpath(__DIR__ . '/..');
    $real = (string) realpath($script);
    if ($real === '' || !str_starts_with($real, $raiz)) {
        return;
    }
    $ruta = str_replace('\\', '/', substr($real, strlen($raiz) + 1));
    $problema = Restricciones::evaluar($ruta, comercial_condiciones(), static fn (Recurso $r): int => comercial_consumo()->de($r));
    if ($problema !== null) {
        responder_error($problema['codigo'], $problema['mensaje']);
    }
}

/** Antes de guardar un archivo: que quepa en el almacenamiento del plan. */
function comercial_exigir_espacio(int $bytesNuevos): void
{
    $limite = comercial_condiciones()->limite(Recurso::AlmacenamientoMb);
    if ($limite === null) {
        return;
    }
    require_once __DIR__ . '/subidas.php';
    if (Consumo::bytes(almacen_raiz()) + $bytesNuevos > $limite * 1048576) {
        responder_error(402, "No queda espacio en el plan ({$limite} MB de archivos). Para ampliarlo, contacta con soporte.");
    }
}
