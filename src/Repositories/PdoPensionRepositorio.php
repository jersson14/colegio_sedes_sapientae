<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Monto;
use App\Domain\Pension\DatosPension;
use PDO;
use Throwable;

/** Sobre los SP de pensiones y pagos (migraciones 20261015000000 y 20261019000000 incluidas). */
final class PdoPensionRepositorio implements PensionRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(DatosPension $p): int
    {
        return (int) $this->escalar('SP_REGISTRAR_PENSIONES', [$p->nivel, $p->mes, $p->vencimiento, $p->precio->valor, $p->mora->valor]);
    }

    public function modificar(int $id, DatosPension $p): int
    {
        return (int) $this->escalar('SP_MODIFICAR_PENSIONES', [$id, $p->nivel, $p->mes, $p->vencimiento, $p->precio->valor, $p->mora->valor]);
    }

    public function eliminar(int $id): bool
    {
        return (int) $this->escalar('SP_ELIMINAR_PENSION', [$id]) === 1;
    }

    public function cobrar(array $pagos, int $cobrador): bool
    {
        // Antes se registraban de uno en uno y se cortaba en el primer duplicado: los anteriores
        // quedaban cobrados. Ahora todos o ninguno.
        $propia = !$this->pdo->inTransaction();
        $propia ? $this->pdo->beginTransaction() : $this->pdo->exec('SAVEPOINT cobro');
        $deshacer = function () use ($propia): void {
            $propia ? $this->pdo->rollBack() : $this->pdo->exec('ROLLBACK TO SAVEPOINT cobro');
        };
        try {
            foreach ($pagos as $pago) {
                $codigo = (int) $this->escalar(
                    'SP_REGISTRAR_DETALLE_PENSION_PAGO',
                    [$pago->matricula, $pago->concepto, $pago->pension, $pago->monto->valor, $cobrador]
                );
                if ($codigo !== 1) {
                    $deshacer();
                    return false;
                }
            }
            $propia ? $this->pdo->commit() : $this->pdo->exec('RELEASE SAVEPOINT cobro');
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $deshacer();
            }
            throw $e;
        }
    }

    public function modificarPago(int $id, Monto $monto, string $motivo): bool
    {
        return (int) $this->escalar('SP_MODIFICAR_PAGO_PENSION', [$id, $monto->valor, $motivo]) === 1;
    }

    public function anularPago(int $id, int $anuladoPor): bool
    {
        return (int) $this->escalar('SP_ELIMINAR_PAGO_PENSION', [$id, $anuladoPor]) === 1;
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
