<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * Cómo se despliega el sistema (MODO_TENANT en colegio.env). Es el mismo código en los dos casos:
 * nunca se bifurca el repositorio por paquete comercial.
 *
 * - Unico: una institución por instalación; la base es DB_NAME (hosting compartido, instancia dedicada).
 * - Multiple: varias instituciones en un servidor; el subdominio elige la base en la BD maestra (SaaS).
 */
enum ModoTenant: string
{
    case Unico = 'unico';
    case Multiple = 'multiple';

    public static function desdeConfig(?string $valor): self
    {
        $modo = self::tryFrom(strtolower(trim((string) $valor)) ?: 'unico');
        if ($modo === null) {
            // Un error tipográfico no debe caer en silencio a otro modo.
            throw new \UnexpectedValueException("MODO_TENANT inválido: «{$valor}» (unico|multiple).");
        }
        return $modo;
    }
}
