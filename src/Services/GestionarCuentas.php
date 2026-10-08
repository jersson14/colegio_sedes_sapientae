<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Usuario\Contrasena;
use App\Domain\Usuario\EstadoUsuario;
use App\Repositories\UsuarioRepositorio;
use InvalidArgumentException;

/** Cambios del administrador sobre una cuenta existente. */
final class GestionarCuentas
{
    private const LARGO_MAXIMO_USUARIO = 250; // usuario.usu_usuario VARCHAR(250)

    public function __construct(private readonly UsuarioRepositorio $usuarios)
    {
    }

    /**
     * @return bool false si el nombre ya es de otra cuenta
     * @throws InvalidArgumentException si un dato es inválido
     */
    public function modificar(int $id, string $usuario, int $rol, string $correo): bool
    {
        $usuario = trim($usuario);
        if ($id <= 0 || $rol <= 0 || $usuario === '' || mb_strlen($usuario) > self::LARGO_MAXIMO_USUARIO) {
            throw new InvalidArgumentException('Datos de la cuenta no válidos');
        }
        return $this->usuarios->modificar($id, $usuario, $rol, trim($correo));
    }

    /** @throws InvalidArgumentException */
    public function cambiarContrasena(int $id, string $clave): void
    {
        if ($id <= 0 || $clave === '') {
            throw new InvalidArgumentException('Contraseña no válida');
        }
        $this->usuarios->cambiarContrasena($id, Contrasena::hash($clave));
    }

    /** @throws InvalidArgumentException si el estado no es ACTIVO ni INACTIVO */
    public function cambiarEstado(int $id, string $estado): void
    {
        $valor = EstadoUsuario::tryFrom(strtoupper(trim($estado)));
        if ($id <= 0 || $valor === null) {
            throw new InvalidArgumentException('Estado de usuario no válido');
        }
        $this->usuarios->cambiarEstado($id, $valor);
    }
}
