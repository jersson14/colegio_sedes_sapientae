<?php

declare(strict_types=1);

namespace App\Domain\Horario;

use App\Support\Lote;
use InvalidArgumentException;

/** Un curso en una hora del aula y un día (claves del JSON «componentes» de js/console_horarios.js). */
final class Clase
{
    private const DIAS = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES']; // ENUM horarios.dia

    private function __construct(
        public readonly int $hora,
        public readonly int $curso,
        public readonly string $dia,
    ) {
    }

    /**
     * @param array<mixed> $r
     * @throws InvalidArgumentException si un id no es válido o el día no es de lunes a viernes
     *                                  (antes se guardaba vacío)
     */
    public static function desdeArreglo(array $r): self
    {
        $dia = strtoupper(trim((string) ($r['dia'] ?? '')));
        if (!in_array($dia, self::DIAS, true)) {
            throw new InvalidArgumentException('Día no válido');
        }
        return new self(Lote::idPositivo($r['idhora'] ?? null, 'hora'), Lote::idPositivo($r['idasig'] ?? null, 'curso'), $dia);
    }
}
