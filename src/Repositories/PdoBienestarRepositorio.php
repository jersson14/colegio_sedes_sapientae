<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Comunicado\Comunicado;
use App\Domain\Salud\Atencion;
use App\Domain\Salud\TipoAtencion;
use PDO;

/** Sobre los SP de atenciones y comunicados (migración 20261021000000 incluida). */
final class PdoBienestarRepositorio implements BienestarRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrarAtencion(TipoAtencion $tipo, Atencion $a, int $profesional): void
    {
        $this->escalar($tipo->procedimientoRegistrar(), [$a->matricula, $a->motivo, $a->diagnostico, $a->observaciones, $profesional]);
    }

    public function modificarAtencion(TipoAtencion $tipo, int $id, Atencion $a): bool
    {
        // El último parámetro (IDUSU) se conserva en la firma del SP pero se ignora.
        return (int) $this->escalar($tipo->procedimientoModificar(), [$id, $a->matricula, $a->motivo, $a->diagnostico, $a->observaciones, 0]) === 1;
    }

    public function registrarComunicado(Comunicado $c, string $imagen, int $autor): int
    {
        return (int) $this->escalar('SP_REGISTRAR_COMUNICADOS', [$c->tipo, $c->aula, $c->titulo, $c->descripcion, $imagen, $autor]);
    }

    public function modificarComunicado(int $id, Comunicado $c, string $imagen): bool
    {
        // El último parámetro (USU) se conserva en la firma del SP pero se ignora.
        return (int) $this->escalar('SP_MODIFICAR_COMUNICADO', [$id, $c->tipo, $c->aula, $c->titulo, $c->descripcion, $c->estado, $imagen, 0]) === 1;
    }

    public function imagenDeComunicado(int $id): ?string
    {
        $q = $this->pdo->prepare('SELECT imagen FROM comunicados WHERE id_comunicado = ?');
        $q->execute([$id]);
        $imagen = $q->fetchColumn();
        $q->closeCursor();
        return $imagen === false ? null : (string) $imagen;
    }

    public function eliminarComunicado(int $id): bool
    {
        return (int) $this->escalar('SP_ELIMINAR_COMUNICADO', [$id]) === 1;
    }

    /** @param list<int|string> $parametros */
    private function escalar(string $procedimiento, array $parametros): mixed
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        $valor = $consulta->columnCount() > 0 ? $consulta->fetchColumn() : null;
        $consulta->closeCursor();
        return $valor;
    }
}
