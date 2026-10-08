<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;
use PDO;
use Throwable;

/** Sobre los procedimientos existentes (migración 20261012000000 incluida). */
final class PdoMatriculaRepositorio implements MatriculaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(int $alumno, DatosMatricula $datos, CuentaNueva $cuenta, int $cobrador): ResultadoRegistro
    {
        $parametros = [$alumno, ...$this->datos($datos), $cuenta->usuario, $cuenta->hashContrasena, $cuenta->correo, $cobrador];
        // Cuenta, matrícula, tres pagos y tres ingresos: todo o nada (el SP no usa transacción).
        $codigo = (int) $this->enTransaccion(fn () => $this->escalar('SP_REGISTRAR_MATRICULA', $parametros));
        return ResultadoRegistro::tryFrom($codigo) ?? ResultadoRegistro::Invalida;
    }

    public function modificar(int $id, DatosMatricula $datos): int
    {
        // IDESTU se conserva en la firma del SP pero se ignora: el alumno de una matrícula no cambia.
        return (int) $this->escalar('SP_MODIFICAR_MATRICULA', [$id, 0, ...$this->datos($datos)]);
    }

    public function eliminar(int $id): int
    {
        // Matrícula, alumno de vuelta a NUEVO y su cuenta: todo o nada.
        return (int) $this->enTransaccion(fn () => $this->escalar('SP_ELIMINAR_MATRICULA', [$id]));
    }

    /** Si ya hay una transacción abierta (la de quien llama), se suma a ella en lugar de anidar. */
    private function enTransaccion(callable $trabajo): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $trabajo();
        }
        $this->pdo->beginTransaction();
        try {
            $resultado = $trabajo();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return list<int|string> en el orden de los SP: año, aula, montos, procedencia, provincia, departamento */
    private function datos(DatosMatricula $d): array
    {
        return [$d->anio, $d->aula, $d->admision->valor, $d->alumnoNuevo->valor, $d->matricula->valor,
            $d->procedencia, $d->provincia, $d->departamento];
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
