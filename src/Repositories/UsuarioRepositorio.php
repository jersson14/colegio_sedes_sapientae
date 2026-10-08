<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Usuario\EstadoUsuario;

interface UsuarioRepositorio
{
    /**
     * Cuentas con ese nombre de usuario (sin distinguir mayúsculas), con su hash y los datos de
     * la persona que necesita la sesión. Puede haber más de una en datos antiguos.
     *
     * @return list<array<string, mixed>>
     */
    public function buscarPorUsuario(string $usuario): array;

    /** @return bool false si el nombre ya pertenece a otra cuenta (no se modifica nada) */
    public function modificar(int $id, string $usuario, int $rol, string $correo): bool;

    public function cambiarContrasena(int $id, string $hash): void;

    public function cambiarEstado(int $id, EstadoUsuario $estado): void;
}
