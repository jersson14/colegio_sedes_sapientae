<?php

declare(strict_types=1);

/**
 * Marca de la institución de la petición (Fase 4.7) para index.php y view/index.php:
 *   <?= marca_html(marca()->nombre) ?>   <img src="<?= marca_html($base . marca()->logoPanel) ?>">
 */

require_once __DIR__ . '/tenant.php';
require_once __DIR__ . '/subidas.php';
require_once __DIR__ . '/../model/model_conexion.php';

use App\Core\Conexion;
use App\Tenancy\Marca;
use App\Tenancy\ResolverTenant;

function marca(): Marca
{
    static $marca = null;
    if ($marca === null) {
        tenant_actual();
        try {
            $pdo = Conexion::crear();
        } catch (PDOException $e) {
            // La página de acceso se muestra igual; el fallo se verá al iniciar sesión.
            error_log('Marca sin BD: ' . $e->getMessage());
            $pdo = null;
        }
        $marca = Marca::deLaInstitucion(
            $pdo,
            ResolverTenant::desdeConfig()->modo(),
            static fn (string $ruta): bool => subida_ubicar($ruta, dirname($ruta)) !== null,
        );
    }
    return $marca;
}

function marca_html(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
