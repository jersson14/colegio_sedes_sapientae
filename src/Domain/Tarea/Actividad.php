<?php

declare(strict_types=1);

namespace App\Domain\Tarea;

use App\Support\Lote;
use App\Support\Texto;
use DateTimeImmutable;
use InvalidArgumentException;

/** Datos de una tarea o un examen de un curso (formularios de js/console_tareas*.js y console_examen*.js). */
final class Actividad
{
    private function __construct(
        public readonly int $curso,
        public readonly string $tema,
        public readonly string $fecha,
        public readonly string $descripcion,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @param int $largoTema 150 en tareas (parámetro del SP), 255 en exámenes
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post, int $largoTema): self
    {
        $tema = Texto::deFormulario($post['tema'] ?? '');
        $descripcion = Texto::deFormulario($post['descrip'] ?? '');
        Texto::exigirLargo('tema', $tema, $largoTema, obligatorio: true);
        Texto::exigirLargo('descripción', $descripcion, 255);
        return new self(Lote::idPositivo($post['asig'] ?? null, 'curso'), $tema, self::fecha($post['fecha'] ?? ''), $descripcion);
    }

    /**
     * Fecha («2025-12-30») o fecha y hora («2025-12-30T10:00», «2025-12-30 10:00:00»).
     *
     * @throws InvalidArgumentException
     */
    public static function fecha(mixed $texto): string
    {
        $texto = trim((string) $texto);
        foreach (['!Y-m-d', 'Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $formato) {
            $f = DateTimeImmutable::createFromFormat($formato, $texto);
            if ($f !== false && $f->format(ltrim($formato, '!')) === $texto) {
                return $f->format('Y-m-d H:i:s');
            }
        }
        throw new InvalidArgumentException('Fecha no válida');
    }
}
