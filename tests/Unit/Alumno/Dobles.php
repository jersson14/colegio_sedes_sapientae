<?php

declare(strict_types=1);

namespace Tests\Unit\Alumno;

use App\Domain\Alumno\FichaAlumno;
use App\Repositories\AlumnoRepositorio;
use App\Support\AlmacenFotos;

/** Mismas reglas que los SP (migración 20261011000000): DNI único, sin baja si hay matrícula. */
final class AlumnoRepositorioEnMemoria implements AlumnoRepositorio
{
    /** @var array<int, array{dni: string, foto: string, ficha: FichaAlumno}> */
    public array $alumnos = [];
    /** @var list<string> DNIs con matrícula */
    public array $matriculados = [];

    public function registrar(FichaAlumno $ficha, string $rutaFoto): bool
    {
        if ($this->idPorDni($ficha->dni) !== null) {
            return false;
        }
        $this->alumnos[count($this->alumnos) + 1] = ['dni' => $ficha->dni, 'foto' => $rutaFoto, 'ficha' => $ficha];
        return true;
    }

    public function modificar(int $id, FichaAlumno $ficha, string $rutaFoto): bool
    {
        $otro = $this->idPorDni($ficha->dni);
        if ($otro !== null && $otro !== $id) {
            return false;
        }
        $this->alumnos[$id] = ['dni' => $ficha->dni, 'foto' => $rutaFoto, 'ficha' => $ficha];
        return true;
    }

    public function fotoPorId(int $id): ?string
    {
        return $this->alumnos[$id]['foto'] ?? null;
    }

    public function fotoPorDni(string $dni): ?string
    {
        $id = $this->idPorDni($dni);
        return $id === null ? null : $this->alumnos[$id]['foto'];
    }

    public function eliminarPorDni(string $dni): bool
    {
        $id = $this->idPorDni($dni);
        if ($id === null || in_array($dni, $this->matriculados, true)) {
            return false;
        }
        unset($this->alumnos[$id]);
        return true;
    }

    public function cambiarFotoPorDni(string $dni, string $rutaFoto): void
    {
        $id = $this->idPorDni($dni);
        if ($id !== null) {
            $this->alumnos[$id]['foto'] = $rutaFoto;
        }
    }

    private function idPorDni(string $dni): ?int
    {
        foreach ($this->alumnos as $id => $a) {
            if ($a['dni'] === $dni) {
                return $id;
            }
        }
        return null;
    }
}

/** Registra qué se validó, guardó y borró, sin tocar el disco. */
final class FotosEnMemoria implements AlmacenFotos
{
    public int $validadas = 0;
    /** @var list<string> */
    public array $guardadas = [];
    /** @var list<string> */
    public array $borradas = [];
    public bool $fallarAlGuardar = false;

    public function validarNueva(): string
    {
        return 'fotos/NUEVA' . ++$this->validadas . '.png';
    }

    public function guardar(string $ruta): bool
    {
        if ($this->fallarAlGuardar) {
            return false;
        }
        $this->guardadas[] = $ruta;
        return true;
    }

    public function borrar(string $ruta): void
    {
        $this->borradas[] = $ruta;
    }

    public function rutaSinFoto(): string
    {
        return 'fotos/';
    }
}
