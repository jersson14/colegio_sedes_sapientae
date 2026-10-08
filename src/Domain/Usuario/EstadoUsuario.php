<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/** Valores del ENUM usuario.usu_estatus. */
enum EstadoUsuario: string
{
    case Activo = 'ACTIVO';
    case Inactivo = 'INACTIVO';
}
