<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Usuario\Autenticacion;
use App\Domain\Usuario\Contrasena;
use App\Domain\Usuario\EstadoUsuario;
use App\Domain\Usuario\ResultadoLogin;
use App\Repositories\UsuarioRepositorio;

/** Verifica usuario y contraseña. La sesión y el límite de intentos son cosa del controlador. */
final class AutenticarUsuario
{
    public function __construct(private readonly UsuarioRepositorio $usuarios)
    {
    }

    public function ejecutar(string $usuario, string $clave): Autenticacion
    {
        foreach ($this->usuarios->buscarPorUsuario($usuario) as $cuenta) {
            if (!Contrasena::verificar($clave, (string) ($cuenta['usu_contra'] ?? ''))) {
                continue;
            }
            // Como antes: cuenta la primera fila cuya contraseña coincide.
            if (($cuenta['usu_estatus'] ?? '') === EstadoUsuario::Inactivo->value) {
                return Autenticacion::fallida(ResultadoLogin::Inactivo);
            }
            return Autenticacion::correcta($cuenta);
        }
        return Autenticacion::fallida(ResultadoLogin::Incorrecto);
    }
}
