<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Asistencia\Asistencia;
use App\Repositories\AsistenciaRepositorio;
use App\Support\Lote;
use InvalidArgumentException;

/**
 * Registro, edición y borrado de la asistencia de un aula. Los códigos son los que espera
 * js/console_asistencia.js: 1 bien, 2 alguna ya existía / no existe, 0 datos inválidos.
 */
final class GestionarAsistencia
{
    public function __construct(private readonly AsistenciaRepositorio $asistencias)
    {
    }

    /**
     * @param list<array<mixed>> $registros
     * @return int 1 todas registradas, 2 alguna ya existía (no se toca)
     * @throws InvalidArgumentException si alguno es inválido (no se registra ninguno)
     */
    public function registrar(array $registros): int
    {
        $existentes = $this->asistencias->registrar(array_map(Asistencia::nueva(...), $registros));
        return $existentes === 0 ? 1 : 2;
    }

    /**
     * Todas se validan antes de tocar la BD.
     *
     * @param list<array<mixed>> $registros
     * @return int 1 todas editadas, 2 alguna no existe
     * @throws InvalidArgumentException si alguna es inválida (no se edita ninguna)
     */
    public function editar(array $registros): int
    {
        $ok = true;
        foreach (array_map(Asistencia::edicion(...), $registros) as $asistencia) {
            $ok = $this->asistencias->editar($asistencia) && $ok;
        }
        return $ok ? 1 : 2;
    }

    /** @throws InvalidArgumentException */
    public function eliminarDelDia(mixed $fecha, mixed $aula): void
    {
        $this->asistencias->eliminarDelDia(Asistencia::fecha($fecha), Lote::idPositivo($aula, 'aula'));
    }
}
