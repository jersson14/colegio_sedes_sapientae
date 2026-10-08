<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Horario\ResultadoClase;
use PDO;
use Throwable;

/** Sobre los procedimientos existentes (migración 20261018000000 incluida). */
final class PdoHorarioRepositorio implements HorarioRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(array $clases): array
    {
        // Todo el lote o nada. Dentro de una transacción ajena se usa un punto de guardado para deshacer
        // solo lo de este lote (un choque no es una excepción: quien llama no sabría que debe deshacer).
        $propia = !$this->pdo->inTransaction();
        $propia ? $this->pdo->beginTransaction() : $this->pdo->exec('SAVEPOINT lote_horario');
        $deshacer = function () use ($propia): void {
            $propia ? $this->pdo->rollBack() : $this->pdo->exec('ROLLBACK TO SAVEPOINT lote_horario');
        };
        $registradas = 0;
        $yaEstaban = 0;
        try {
            foreach ($clases as $c) {
                $r = ResultadoClase::from((int) $this->escalar('SP_REGISTRAR_HORARIO_AULA', [$c->hora, $c->curso, $c->dia]));
                if ($r->esChoque()) {
                    $deshacer();
                    return ['registradas' => 0, 'yaEstaban' => 0, 'choque' => $r];
                }
                $registradas += $r === ResultadoClase::Registrada ? 1 : 0;
                $yaEstaban += $r === ResultadoClase::YaEstaba ? 1 : 0;
            }
            $propia ? $this->pdo->commit() : $this->pdo->exec('RELEASE SAVEPOINT lote_horario');
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $deshacer();
            }
            throw $e;
        }
        return ['registradas' => $registradas, 'yaEstaban' => $yaEstaban, 'choque' => null];
    }

    public function eliminarDeAula(int $aula, int $anio): void
    {
        $this->escalar('SP_ELIMINAR_HORARIO', [$aula, $anio]);
    }

    public function registrarAsignatura(string $nombre, int $aula, string $observaciones): bool
    {
        return (int) $this->escalar('SP_REGISTRAR_ASIGNATURAS', [$nombre, $aula, $observaciones]) === 1;
    }

    public function modificarAsignatura(int $id, string $nombre, int $aula, string $observaciones): bool
    {
        return (int) $this->escalar('SP_MODIFICAR_ASIGNATURA', [$id, $nombre, $aula, $observaciones]) === 1;
    }

    public function eliminarAsignatura(int $id): bool
    {
        return (int) $this->escalar('ELIMINAR_ASIGNATURA', [$id]) === 1;
    }

    /** @param list<int|string> $parametros */
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
