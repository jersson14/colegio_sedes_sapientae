<?php

declare(strict_types=1);

namespace App\Domain\Matricula;

/** Resultado de registrar una matrícula; el valor es el código que espera js/console_matriculas.js. */
enum ResultadoRegistro: int
{
    case Invalida = 0;
    case Registrada = 1;
    case YaMatriculado = 2;
    case UsuarioOcupado = 3;
}
