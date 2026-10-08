<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Nota\EdicionNota;
use App\Domain\Nota\NotaDePadres;
use App\Domain\Nota\RegistroNota;
use App\Repositories\NotaRepositorio;
use InvalidArgumentException;

/**
 * Registro y edición de notas (del alumno y de los padres). Un lote de registro se valida entero
 * antes de tocar la BD; la edición procesa cada nota por separado e informa cuáles fallaron, como
 * hacía el panel, pero sin exponer los mensajes de la BD.
 */
final class GestionarNotas
{
    public function __construct(private readonly NotaRepositorio $notas)
    {
    }

    /**
     * @param list<array<mixed>> $registros
     * @return int cuántas insertó (las que ya existían no se tocan)
     * @throws InvalidArgumentException si algún registro es inválido (no se guarda ninguno)
     */
    public function registrar(array $registros): int
    {
        return $this->notas->registrar(array_map(RegistroNota::desdeArreglo(...), $registros));
    }

    /**
     * @param list<array<mixed>> $registros
     * @throws InvalidArgumentException si algún registro es inválido (no se guarda ninguno)
     */
    public function registrarDePadres(array $registros): int
    {
        return $this->notas->registrarDePadres(array_map(NotaDePadres::desdeArreglo(...), $registros));
    }

    /**
     * @param list<array<mixed>> $registros
     * @return array{actualizadas: int, errores: list<string>}
     */
    public function editar(array $registros): array
    {
        return $this->editarCada($registros, EdicionNota::delAlumno(...), $this->notas->editar(...));
    }

    /**
     * @param list<array<mixed>> $registros
     * @return array{actualizadas: int, errores: list<string>}
     */
    public function editarDePadres(array $registros): array
    {
        return $this->editarCada($registros, EdicionNota::deLosPadres(...), $this->notas->editarDePadres(...));
    }

    /**
     * @param list<array<mixed>> $registros
     * @param callable(array<mixed>): EdicionNota $leer
     * @param callable(EdicionNota): bool $guardar
     * @return array{actualizadas: int, errores: list<string>}
     */
    private function editarCada(array $registros, callable $leer, callable $guardar): array
    {
        $actualizadas = 0;
        $errores = [];
        foreach ($registros as $i => $registro) {
            try {
                $edicion = $leer($registro);
            } catch (InvalidArgumentException $e) {
                $errores[] = 'Registro ' . ($i + 1) . ': ' . $e->getMessage();
                continue;
            }
            if ($guardar($edicion)) {
                $actualizadas++;
            } else {
                $errores[] = "No se encontró la nota con ID {$edicion->id}";
            }
        }
        return ['actualizadas' => $actualizadas, 'errores' => $errores];
    }
}
