<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/** Resultado de un intento de inicio de sesión; el valor es el código que espera el JS del login. */
enum ResultadoLogin: int
{
    case Incorrecto = 0;
    case Correcto = 1;
    case Inactivo = 2;
}
