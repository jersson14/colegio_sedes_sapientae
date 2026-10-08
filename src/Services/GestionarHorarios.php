<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Horario\Clase;
use App\Repositories\HorarioRepositorio;
use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/**
 * Asignaturas y horarios de las aulas. Los códigos son los que espera js/console_horarios.js y
 * js/console_asignaturas.js, más 3 (celda ocupada) y 4 (docente ocupado) para los horarios.
 */
final class GestionarHorarios
{
    public function __construct(private readonly HorarioRepositorio $horarios)
    {
    }

    /**
     * Registrar: 1 todas nuevas, 2 alguna ya estaba (las demás se registran), 3/4 choque (ninguna).
     * Modificar (añadir filas al editar): 1 si se añadió alguna, 2 si ya estaban todas, 3/4 choque.
     *
     * @param list<array<mixed>> $componentes
     * @throws InvalidArgumentException si alguna clase es inválida (no se registra ninguna)
     */
    public function registrar(array $componentes, bool $alEditar = false): int
    {
        $r = $this->horarios->registrar(array_map(Clase::desdeArreglo(...), $componentes));
        if ($r['choque'] !== null) {
            return $r['choque']->value;
        }
        if ($alEditar) {
            return $r['registradas'] > 0 ? 1 : 2;
        }
        return $r['yaEstaban'] === 0 ? 1 : 2;
    }

    /** @throws InvalidArgumentException */
    public function eliminarDeAula(mixed $aula, mixed $anio): void
    {
        $this->horarios->eliminarDeAula(Lote::idPositivo($aula, 'aula'), Lote::idPositivo($anio, 'año escolar'));
    }

    /**
     * @return bool false si ya existe en el aula
     * @throws InvalidArgumentException
     */
    public function registrarAsignatura(mixed $nombre, mixed $aula, mixed $observaciones): bool
    {
        [$nombre, $aula, $observaciones] = self::asignatura($nombre, $aula, $observaciones);
        return $this->horarios->registrarAsignatura($nombre, $aula, $observaciones);
    }

    /**
     * @return bool false si ya existe otra con ese nombre en el aula
     * @throws InvalidArgumentException
     */
    public function modificarAsignatura(mixed $id, mixed $nombre, mixed $aula, mixed $observaciones): bool
    {
        [$nombre, $aula, $observaciones] = self::asignatura($nombre, $aula, $observaciones);
        return $this->horarios->modificarAsignatura(Lote::idPositivo($id, 'asignatura'), $nombre, $aula, $observaciones);
    }

    /** @throws InvalidArgumentException */
    public function eliminarAsignatura(mixed $id): bool
    {
        return $this->horarios->eliminarAsignatura(Lote::idPositivo($id, 'asignatura'));
    }

    /**
     * @return array{string, int, string}
     * @throws InvalidArgumentException
     */
    private static function asignatura(mixed $nombre, mixed $aula, mixed $observaciones): array
    {
        $nombre = Texto::deFormulario($nombre);
        $observaciones = Texto::deFormulario($observaciones);
        Texto::exigirLargo('asignatura', $nombre, 255, obligatorio: true);
        Texto::exigirLargo('observaciones', $observaciones, 255);
        return [$nombre, Lote::idPositivo($aula, 'aula'), $observaciones];
    }
}
