<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Asistencia\Asistencia;
use PDO;
use Throwable;

/** Sobre los procedimientos existentes (migración 20261014000000 incluida). */
final class PdoAsistenciaRepositorio implements AsistenciaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(array $asistencias): int
    {
        // Antes se registraban de una en una: un error a mitad dejaba el aula a medias.
        $propia = !$this->pdo->inTransaction();
        if ($propia) {
            $this->pdo->beginTransaction();
        }
        try {
            $existentes = 0;
            foreach ($asistencias as $a) {
                $codigo = (int) $this->escalar('SP_REGISTRAR_ASISTENCIA', [$a->id, $a->fecha, $a->estado->value, $a->observacion]);
                $existentes += $codigo === 2 ? 1 : 0;
            }
            if ($propia) {
                $this->pdo->commit();
            }
            return $existentes;
        } catch (Throwable $e) {
            if ($propia) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function editar(Asistencia $asistencia): bool
    {
        // El SP conserva el parámetro FECHA por compatibilidad, pero ya no cambia la fecha.
        $codigo = $this->escalar('SP_ACTUALIZAR_ASISTENCIA', [$asistencia->id, null, $asistencia->estado->value, $asistencia->observacion]);
        return (int) $codigo === 1;
    }

    public function eliminarDelDia(string $fecha, int $aula): void
    {
        $consulta = $this->pdo->prepare('CALL SP_ELIMINAR_ASISTENCIA_POR_FECHA_Y_AULA(?, ?)');
        $consulta->execute([$fecha, $aula]);
        $consulta->closeCursor();
    }

    /** @param list<int|string|null> $parametros */
    private function escalar(string $procedimiento, array $parametros): mixed
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        $valor = $consulta->fetchColumn();
        $consulta->closeCursor();
        return $valor;
    }
}
