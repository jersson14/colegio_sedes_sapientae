<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;
use App\Repositories\MatriculaRepositorio;

/**
 * Registro, cambios y baja de matrículas. Las reglas de negocio viven en los SP (ver la migración
 * 20261012000000); aquí se validan los datos y se fija quién cobra (el usuario de la sesión).
 */
final class GestionarMatriculas
{
    public function __construct(private readonly MatriculaRepositorio $matriculas)
    {
    }

    public function registrar(int $alumno, DatosMatricula $datos, CuentaNueva $cuenta, int $cobrador): ResultadoRegistro
    {
        if ($alumno <= 0 || $cobrador <= 0) {
            return ResultadoRegistro::Invalida;
        }
        return $this->matriculas->registrar($alumno, $datos, $cuenta, $cobrador);
    }

    /** @return int 1 modificada, 2 ya matriculado ese año, 0 no existe */
    public function modificar(int $id, DatosMatricula $datos): int
    {
        return $id > 0 ? $this->matriculas->modificar($id, $datos) : 0;
    }

    /** @return int 1 eliminada, 2 tiene registros o ingresos válidos, 0 no existe */
    public function eliminar(int $id): int
    {
        return $id > 0 ? $this->matriculas->eliminar($id) : 0;
    }
}
