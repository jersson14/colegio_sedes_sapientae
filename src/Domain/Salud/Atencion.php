<?php

declare(strict_types=1);

namespace App\Domain\Salud;

use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/** Atención de enfermería o psicología a un alumno (formularios de js/console_atencion_*.js). */
final class Atencion
{
    private function __construct(
        public readonly int $matricula,
        public readonly string $motivo,
        public readonly string $diagnostico,
        public readonly string $observaciones,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $campos = [];
        foreach (['motivo' => 'motivo', 'diagno' => 'diagnóstico', 'observa' => 'observaciones'] as $clave => $nombre) {
            $campos[$clave] = Texto::deFormulario($post[$clave] ?? '');
            Texto::exigirLargo($nombre, $campos[$clave], 255, obligatorio: $clave === 'motivo');
        }
        return new self(Lote::idPositivo($post['estu'] ?? null, 'matrícula'), $campos['motivo'], $campos['diagno'], $campos['observa']);
    }
}
