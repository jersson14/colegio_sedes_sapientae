<?php

declare(strict_types=1);

namespace App\Domain\Asistencia;

use App\Support\Lote;
use App\Support\TextoLibre;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Registro o edición de la asistencia de un alumno en un día (claves del JSON de
 * js/console_asistencia.js). La observación se guarda escapada: el panel la pinta como HTML.
 */
final class Asistencia
{
    private const LARGO_OBSERVACION = 1000; // asistencia.observacion VARCHAR(1000)

    private function __construct(
        public readonly int $id,
        public readonly string $fecha,
        public readonly EstadoAsistencia $estado,
        public readonly string $observacion,
    ) {
    }

    /**
     * Nueva: «id» es la matrícula.
     *
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function nueva(array $r): self
    {
        return new self(
            Lote::idPositivo($r['id_matri'] ?? null, 'matrícula'),
            self::fecha($r['fecha'] ?? ''),
            self::estado($r['esta'] ?? ''),
            TextoLibre::desde($r['obse'] ?? '', 'observación', self::LARGO_OBSERVACION),
        );
    }

    /**
     * Edición: «id» es el de la asistencia; la fecha no cambia (el panel no la deja editar).
     *
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function edicion(array $r): self
    {
        return new self(
            Lote::idPositivo($r['id_asis'] ?? null, 'asistencia'),
            '',
            self::estado($r['esta'] ?? ''),
            TextoLibre::desde($r['obse'] ?? '', 'observación', self::LARGO_OBSERVACION),
        );
    }

    /** @throws InvalidArgumentException */
    public static function fecha(mixed $texto): string
    {
        $texto = trim((string) $texto);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
            throw new InvalidArgumentException('Fecha no válida');
        }
        return $texto;
    }

    /** @throws InvalidArgumentException */
    private static function estado(mixed $texto): EstadoAsistencia
    {
        return EstadoAsistencia::tryFrom(strtoupper(trim((string) $texto)))
            ?? throw new InvalidArgumentException('Estado de asistencia no válido');
    }
}
