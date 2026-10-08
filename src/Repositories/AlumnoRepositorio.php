<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Alumno\FichaAlumno;

interface AlumnoRepositorio
{
    /** @return bool false si el DNI ya está registrado (no se guarda nada) */
    public function registrar(FichaAlumno $ficha, string $rutaFoto): bool;

    /** @return bool false si el DNI nuevo pertenece a otro alumno (no se modifica nada) */
    public function modificar(int $id, FichaAlumno $ficha, string $rutaFoto): bool;

    /** Ruta guardada de la foto, o null si el alumno no existe. */
    public function fotoPorId(int $id): ?string;

    public function fotoPorDni(string $dni): ?string;

    /** @return bool false si tiene matrícula o no existe (no se elimina nada) */
    public function eliminarPorDni(string $dni): bool;

    public function cambiarFotoPorDni(string $dni, string $rutaFoto): void;
}
