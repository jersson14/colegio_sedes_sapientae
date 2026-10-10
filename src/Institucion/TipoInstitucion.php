<?php

declare(strict_types=1);

namespace App\Institucion;

/** Qué clase de institución es (Fase 5): de eso dependen los valores por defecto de su configuración. */
enum TipoInstitucion: string
{
    case Colegio = 'COLEGIO';
    case Instituto = 'INSTITUTO';
    case Cetpro = 'CETPRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Colegio => 'Colegio',
            self::Instituto => 'Instituto de educación superior',
            self::Cetpro => 'Centro de educación técnico-productiva',
        };
    }
}
