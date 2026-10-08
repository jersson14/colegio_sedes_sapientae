<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Asistencia\Asistencia;

interface AsistenciaRepositorio
{
    /**
     * Registra las que no existan (matrícula, fecha), todas o ninguna.
     *
     * @param list<Asistencia> $asistencias
     * @return int cuántas ya existían (no se tocan)
     */
    public function registrar(array $asistencias): int;

    /** @return bool false si la asistencia no existe */
    public function editar(Asistencia $asistencia): bool;

    public function eliminarDelDia(string $fecha, int $aula): void;
}
