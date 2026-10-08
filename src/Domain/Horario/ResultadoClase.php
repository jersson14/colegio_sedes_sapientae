<?php

declare(strict_types=1);

namespace App\Domain\Horario;

/** Respuesta de SP_REGISTRAR_HORARIO_AULA (migración 20261018000000). */
enum ResultadoClase: int
{
    case Registrada = 1;
    case YaEstaba = 2;
    case CeldaOcupada = 3;
    case DocenteOcupado = 4;

    public function esChoque(): bool
    {
        return $this === self::CeldaOcupada || $this === self::DocenteOcupado;
    }
}
