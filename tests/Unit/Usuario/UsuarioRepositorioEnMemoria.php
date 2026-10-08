<?php

declare(strict_types=1);

namespace Tests\Unit\Usuario;

use App\Domain\Usuario\EstadoUsuario;
use App\Repositories\UsuarioRepositorio;

/** Doble de prueba: mismas reglas que los SP (nombre único sin distinguir mayúsculas). */
final class UsuarioRepositorioEnMemoria implements UsuarioRepositorio
{
    /** @param array<int, array<string, mixed>> $cuentas usu_id => fila */
    public function __construct(public array $cuentas = [])
    {
    }

    public function buscarPorUsuario(string $usuario): array
    {
        return array_values(array_filter(
            $this->cuentas,
            static fn (array $c): bool => strcasecmp((string) $c['usu_usuario'], $usuario) === 0
        ));
    }

    public function modificar(int $id, string $usuario, int $rol, string $correo): bool
    {
        foreach ($this->cuentas as $otroId => $c) {
            if ($otroId !== $id && strcasecmp((string) $c['usu_usuario'], $usuario) === 0) {
                return false;
            }
        }
        $this->cuentas[$id] = ['usu_usuario' => $usuario, 'rol_id' => $rol, 'usu_email' => $correo] + ($this->cuentas[$id] ?? []);
        return true;
    }

    public function cambiarContrasena(int $id, string $hash): void
    {
        $this->cuentas[$id]['usu_contra'] = $hash;
    }

    public function cambiarEstado(int $id, EstadoUsuario $estado): void
    {
        $this->cuentas[$id]['usu_estatus'] = $estado->value;
    }
}
