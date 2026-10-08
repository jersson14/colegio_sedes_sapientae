<?php

declare(strict_types=1);

/**
 * Carga de las clases de src/ (namespace App\, PSR-4) desde el código heredado.
 *
 * No depende de vendor/: producción puede no tener Composer instalado y el código heredado
 * no carga vendor/autoload.php. Si Composer está, su autoloader resuelve lo mismo.
 */
spl_autoload_register(static function (string $clase): void {
    if (!str_starts_with($clase, 'App\\')) {
        return;
    }
    $archivo = __DIR__ . '/../src/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});
