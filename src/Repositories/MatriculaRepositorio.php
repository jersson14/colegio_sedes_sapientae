<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;

interface MatriculaRepositorio
{
    /** Registra la matrícula, sus tres pagos con sus ingresos y, si el alumno es NUEVO, su cuenta. */
    public function registrar(int $alumno, DatosMatricula $datos, CuentaNueva $cuenta, int $cobrador): ResultadoRegistro;

    /** @return int 1 modificada, 2 el alumno ya tiene matrícula ese año, 0 no existe */
    public function modificar(int $id, DatosMatricula $datos): int;

    /** @return int 1 eliminada, 2 tiene registros o ingresos válidos, 0 no existe */
    public function eliminar(int $id): int;
}
