<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Tarea\Actividad;
use PDO;

/** Sobre los SP de tareas y exámenes (migración 20261020000000 incluida). */
final class PdoTareaRepositorio implements TareaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function publicar(Actividad $t, string $carpeta): int
    {
        return (int) $this->escalar('SP_REGISTRAR_TAREA', [$t->curso, $t->tema, $t->fecha, $t->descripcion, $carpeta]);
    }

    public function modificar(string $id, Actividad $t, string $carpeta): int
    {
        return (int) $this->escalar('SP_MODIFICAR_TAREAS', [$id, $t->curso, $t->tema, $t->fecha, $t->descripcion, $carpeta]);
    }

    public function carpetaDeTarea(string $id): ?string
    {
        $q = $this->pdo->prepare('SELECT archivo_tarea FROM tareas WHERE id_tarea = ?');
        $q->execute([$id]);
        $carpeta = $q->fetchColumn();
        $q->closeCursor();
        return $carpeta === false ? null : (string) $carpeta;
    }

    public function eliminar(string $id): bool
    {
        return (int) $this->escalar('SP_ELIMINAR_TAREA', [$id]) === 1;
    }

    public function finalizar(string $id): bool
    {
        return (int) $this->escalar('SP_MODIFICAR_TAREA_ESTATUS', [$id, 'FINALIZADO']) === 1;
    }

    public function entregar(int $detalle, string $carpeta, bool $reemplazo): bool
    {
        $sp = $reemplazo ? 'SP_MODIFICAR_ENVIAR_TAREA' : 'SP_ENVIAR_TAREA';
        return (int) $this->escalar($sp, [$detalle, $carpeta]) === 1;
    }

    public function calificar(int $detalle, int $nota, string $observacion): bool
    {
        return (int) $this->escalar('SP_REGISTRAR_CALIFICACION', [$detalle, $nota, $observacion]) === 1;
    }

    public function registrarExamen(Actividad $e): int
    {
        return (int) $this->escalar('SP_REGISTRAR_EXAMENES', [$e->curso, $e->tema, $e->fecha, $e->descripcion]);
    }

    public function modificarExamen(string $id, Actividad $e): int
    {
        return (int) $this->escalar('SP_MODIFICAR_EXAMENES', [$id, $e->curso, $e->tema, $e->fecha, $e->descripcion]);
    }

    public function estadoExamen(string $id, string $estado): bool
    {
        return (int) $this->escalar('SP_MODIFICAR_EXAMEN_ESTATUS', [$id, $estado]) === 1;
    }

    public function eliminarExamen(string $id): void
    {
        $this->escalar('SP_ELIMINAR_EXAMEN', [$id]);
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
