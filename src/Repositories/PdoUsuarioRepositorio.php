<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Usuario\EstadoUsuario;
use PDO;

/** Implementación sobre los procedimientos almacenados existentes (la lógica SQL sigue en la BD). */
final class PdoUsuarioRepositorio implements UsuarioRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorUsuario(string $usuario): array
    {
        /** @var list<array<string, mixed>> */
        return $this->llamar('SP_VERIFICAR_USUARIO', [$usuario])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function modificar(int $id, string $usuario, int $rol, string $correo): bool
    {
        return (int) $this->escalar('SP_MODIFICAR_USUARIO', [$id, $usuario, $rol, $correo]) === 1;
    }

    public function cambiarContrasena(int $id, string $hash): void
    {
        $this->llamar('SP_MODIFICAR_USUARIO_CONTRA', [$id, $hash])->closeCursor();
    }

    public function cambiarEstado(int $id, EstadoUsuario $estado): void
    {
        $this->escalar('SP_MODIFICAR_USUARIO_ESTATUS', [$id, $estado->value]);
    }

    /** @param list<int|string> $parametros */
    private function llamar(string $procedimiento, array $parametros): \PDOStatement
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        return $consulta;
    }

    /** @param list<int|string> $parametros */
    private function escalar(string $procedimiento, array $parametros): mixed
    {
        $consulta = $this->llamar($procedimiento, $parametros);
        $valor = $consulta->fetchColumn();
        $consulta->closeCursor();
        return $valor;
    }
}
