<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Nota\EdicionNota;
use App\Domain\Nota\NotaDePadres;
use App\Domain\Nota\RegistroNota;

interface NotaRepositorio
{
    /**
     * Inserta las que no existan (matrícula, periodo, criterio); las existentes no se tocan.
     *
     * @param list<RegistroNota> $notas
     * @return int cuántas insertó
     */
    public function registrar(array $notas): int;

    /**
     * Inserta o actualiza por (matrícula, periodo, competencia).
     *
     * @param list<NotaDePadres> $notas
     * @return int cuántas procesó
     */
    public function registrarDePadres(array $notas): int;

    /** @return bool false si la nota no existe */
    public function editar(EdicionNota $edicion): bool;

    /** @return bool false si la nota no existe */
    public function editarDePadres(EdicionNota $edicion): bool;
}
