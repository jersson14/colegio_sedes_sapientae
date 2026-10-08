<?php

declare(strict_types=1);

namespace App\Domain\Salud;

/** Tipo de atención (ENUM atencion_salud.tipo_atencion) con sus procedimientos. */
enum TipoAtencion: string
{
    case Enfermeria = 'ENFERMERIA';
    case Psicologia = 'PSICOLOGIA';

    public function procedimientoRegistrar(): string
    {
        return $this === self::Enfermeria ? 'SP_REGISTRAR_ATENCION_ENFERME' : 'SP_REGISTRAR_ATENCION_PSICO';
    }

    /** Solo modifica atenciones de este tipo (migración 20261021000000). */
    public function procedimientoModificar(): string
    {
        return $this === self::Enfermeria ? 'SP_MODIFICAR_ATENCION_ENFERMERIA' : 'SP_MODIFICAR_ATENCION_PSICOLOGICA';
    }
}
