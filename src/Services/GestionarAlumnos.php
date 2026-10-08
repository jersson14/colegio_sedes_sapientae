<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Alumno\FichaAlumno;
use App\Repositories\AlumnoRepositorio;
use App\Support\AlmacenFotos;

/**
 * Altas, cambios y bajas de alumnos con su foto. Orden en cada operación: la foto se valida antes
 * de tocar la BD; se guarda (y se borra la anterior) solo si la BD aceptó el cambio.
 */
final class GestionarAlumnos
{
    public function __construct(
        private readonly AlumnoRepositorio $alumnos,
        private readonly AlmacenFotos $fotos,
    ) {
    }

    /** @return bool false si el DNI ya está registrado */
    public function registrar(FichaAlumno $ficha, bool $conFoto): bool
    {
        $ruta = $conFoto ? $this->fotos->validarNueva() : $this->fotos->rutaSinFoto();
        $registrado = $this->alumnos->registrar($ficha, $ruta);
        if ($registrado && $conFoto) {
            $this->fotos->guardar($ruta);
        }
        return $registrado;
    }

    /**
     * La foto actual se lee de la BD, no del formulario: el cliente no decide qué ruta queda
     * guardada ni qué archivo se borra.
     *
     * @return bool false si el DNI nuevo pertenece a otro alumno
     */
    public function modificar(int $id, FichaAlumno $ficha, bool $fotoNueva): bool
    {
        $anterior = $this->alumnos->fotoPorId($id) ?? $this->fotos->rutaSinFoto();
        $ruta = $fotoNueva ? $this->fotos->validarNueva() : $anterior;
        $modificado = $this->alumnos->modificar($id, $ficha, $ruta);
        if ($modificado && $fotoNueva && $this->fotos->guardar($ruta)) {
            $this->fotos->borrar($anterior);
        }
        return $modificado;
    }

    /** @return bool false si tiene matrícula o no existe */
    public function eliminar(string $dni): bool
    {
        $foto = $this->alumnos->fotoPorDni($dni);
        $eliminado = $this->alumnos->eliminarPorDni($dni);
        if ($eliminado && $foto !== null) {
            $this->fotos->borrar($foto); // antes la foto quedaba huérfana en el disco
        }
        return $eliminado;
    }

    /** Sin foto nueva, el alumno queda con la imagen por defecto. */
    public function cambiarFoto(string $dni, bool $fotoNueva): void
    {
        $anterior = $this->alumnos->fotoPorDni($dni);
        $ruta = $fotoNueva ? $this->fotos->validarNueva() : $this->fotos->rutaSinFoto();
        $this->alumnos->cambiarFotoPorDni($dni, $ruta);
        $guardada = !$fotoNueva || $this->fotos->guardar($ruta);
        if ($guardada && $anterior !== null && $anterior !== $ruta) {
            $this->fotos->borrar($anterior);
        }
    }
}
