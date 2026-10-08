<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Nota\EdicionNota;
use App\Domain\Nota\NotaDePadres;
use App\Domain\Nota\RegistroNota;
use PDO;

/** Sobre los procedimientos existentes (migraciones 20261009000000 y 20261013000000 incluidas). */
final class PdoNotaRepositorio implements NotaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(array $notas): int
    {
        $json = json_encode(array_map(static fn (RegistroNota $n): array => $n->paraProcedimiento(), $notas), JSON_THROW_ON_ERROR);
        return (int) $this->fila('SP_REGISTRAR_NOTAS', [$json])['inserted_count'];
    }

    public function registrarDePadres(array $notas): int
    {
        $json = json_encode(array_map(static fn (NotaDePadres $n): array => $n->paraProcedimiento(), $notas), JSON_THROW_ON_ERROR);
        return (int) $this->fila('SP_REGISTRAR_NOTAS_PADRES', [$json])['processed_count'];
    }

    public function editar(EdicionNota $edicion): bool
    {
        return (int) $this->fila('SP_EDITAR_NOTAS', [$edicion->id, $edicion->nota->valor, $edicion->texto])['exit_code'] === 1;
    }

    public function editarDePadres(EdicionNota $edicion): bool
    {
        // El SP recibe la competencia antes que la nota.
        return (int) $this->fila('SP_EDITAR_NOTAS_PAPAS', [$edicion->id, $edicion->texto, $edicion->nota->valor])['exit_code'] === 1;
    }

    /**
     * @param list<int|string> $parametros
     * @return array<string, mixed>
     */
    private function fila(string $procedimiento, array $parametros): array
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta->closeCursor();
        return is_array($fila) ? $fila : [];
    }
}
