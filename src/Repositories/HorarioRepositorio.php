<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Horario\Clase;
use App\Domain\Horario\ResultadoClase;

interface HorarioRepositorio
{
    /**
     * Registra las clases nuevas. Si alguna choca (celda ocupada o docente ocupado) no se registra
     * ninguna y se devuelve el primer choque.
     *
     * @param list<Clase> $clases
     * @return array{registradas: int, yaEstaban: int, choque: ?ResultadoClase}
     */
    public function registrar(array $clases): array;

    /** Borra el horario de un aula en un año escolar. */
    public function eliminarDeAula(int $aula, int $anio): void;

    /** Asignaturas */
    public function registrarAsignatura(string $nombre, int $aula, string $observaciones): bool;

    public function modificarAsignatura(int $id, string $nombre, int $aula, string $observaciones): bool;

    /** @return bool false si tiene docente asignado o no existe */
    public function eliminarAsignatura(int $id): bool;
}
