<?php

declare(strict_types=1);

namespace App\Comercial;

/** Lo que el superadministrador activa por institución, aparte del plan. */
enum ModuloOpcional: string
{
    case FacturacionElectronica = 'facturacion_electronica';
    case AsistenteIa = 'asistente_ia';

    public function etiqueta(): string
    {
        return match ($this) {
            self::FacturacionElectronica => 'Facturación electrónica (Nubefact)',
            self::AsistenteIa => 'Asistente de IA',
        };
    }

    /** Si el módulo admite un límite mensual (las consultas de IA, que paga la plataforma). */
    public function tieneLimite(): bool
    {
        return $this === self::AsistenteIa;
    }
}
