<?php

declare(strict_types=1);

namespace App\Domain\Matricula;

use App\Support\Texto;
use App\Domain\Usuario\Contrasena;
use InvalidArgumentException;

/**
 * Cuenta que se crea al matricular a un alumno NUEVO (para uno ANTIGUO el SP reutiliza la suya y
 * estos datos se ignoran). El usuario y el correo se guardan en MAYÚSCULAS, como siempre; el login
 * no distingue mayúsculas.
 */
final class CuentaNueva
{
    private function __construct(
        public readonly string $usuario,
        public readonly string $hashContrasena,
        public readonly string $correo,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $usuario = Texto::deFormulario($post['usu'] ?? '');
        $correo = Texto::deFormulario($post['correo'] ?? '');
        Texto::exigirLargo('usuario', $usuario, 250);
        Texto::exigirLargo('correo', $correo, 255);
        return new self($usuario, Contrasena::hash((string) ($post['contra'] ?? '')), $correo);
    }
}
