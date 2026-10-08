<?php

declare(strict_types=1);

namespace App\Domain\Alumno;

use App\Support\Texto;

/** Datos de los padres de un alumno (tabla padres, 1:1 con alumnos). */
final class Padres
{
    public function __construct(
        public readonly string $dniPapa,
        public readonly string $datosPapa,
        public readonly string $celularPapa,
        public readonly string $dniMama,
        public readonly string $datosMama,
        public readonly string $celularMama,
    ) {
        Texto::exigirLargo('DNI del padre', $dniPapa, 8);
        Texto::exigirLargo('DNI de la madre', $dniMama, 8);
        foreach (['datos del padre' => $datosPapa, 'datos de la madre' => $datosMama,
            'celular del padre' => $celularPapa, 'celular de la madre' => $celularMama] as $campo => $valor) {
            Texto::exigirLargo($campo, $valor, 255);
        }
    }
}
