<?php

declare(strict_types=1);

namespace App\Comercial;

/** Lo que un plan limita (Fase 4B.2). */
enum Recurso: string
{
    case Alumnos = 'alumnos';
    case Usuarios = 'usuarios';
    case AlmacenamientoMb = 'almacenamiento_mb';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Alumnos => 'alumnos activos',
            self::Usuarios => 'usuarios activos',
            self::AlmacenamientoMb => 'MB de archivos',
        };
    }
}
