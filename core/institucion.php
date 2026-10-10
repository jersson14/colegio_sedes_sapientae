<?php

declare(strict_types=1);

/**
 * Configuración académica de la institución de la petición (Fase 5): tipo, periodo, nota mínima, apoderado…
 * El panel la pasa al JavaScript como window.INSTITUCION (institucion_script()).
 */

require_once __DIR__ . '/tenant.php';

use App\Core\Conexion;
use App\Institucion\Configuracion;
use App\Institucion\PdoConfiguracionRepositorio;

function institucion_config(): Configuracion
{
    static $configuracion = null;
    if ($configuracion === null) {
        tenant_actual();
        try {
            $configuracion = (new PdoConfiguracionRepositorio(Conexion::crear()))->cargar();
        } catch (PDOException $e) {
            error_log('Configuración de la institución sin BD: ' . $e->getMessage());
            $configuracion = new Configuracion();
        }
    }
    return $configuracion;
}

/** <script> con la configuración para el JavaScript del panel. */
function institucion_script(): string
{
    return '<script>window.INSTITUCION = ' . json_encode(institucion_config()->paraInterfaz(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';</script>';
}
