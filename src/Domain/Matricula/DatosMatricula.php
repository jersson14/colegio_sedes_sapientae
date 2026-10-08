<?php

declare(strict_types=1);

namespace App\Domain\Matricula;

use App\Domain\Monto;
use App\Support\Texto;
use InvalidArgumentException;

/** Lo que se registra o modifica de una matrícula (sin la cuenta del alumno). */
final class DatosMatricula
{
    /** @throws InvalidArgumentException */
    public function __construct(
        public readonly int $anio,
        public readonly int $aula,
        public readonly Monto $admision,
        public readonly Monto $alumnoNuevo,
        public readonly Monto $matricula,
        public readonly string $procedencia,
        public readonly string $provincia,
        public readonly string $departamento,
    ) {
        if ($anio <= 0 || $aula <= 0) {
            throw new InvalidArgumentException('Año escolar y aula son obligatorios');
        }
        Texto::exigirLargo('colegio de procedencia', $procedencia, 100);
        Texto::exigirLargo('provincia', $provincia, 50);
        Texto::exigirLargo('departamento', $departamento, 50);
    }

    /**
     * Desde el formulario del panel (mismos nombres de campo que js/console_matriculas.js).
     *
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $texto = static fn (string $campo): string => Texto::deFormulario($post[$campo] ?? '');
        return new self(
            (int) ($post['año'] ?? 0),
            (int) ($post['aula'] ?? 0),
            Monto::desde($post['admi'] ?? '', 'admisión'),
            Monto::desde($post['nuevo'] ?? '', 'alumno nuevo'),
            Monto::desde($post['matri'] ?? '', 'matrícula'),
            $texto('proce'),
            $texto('pro'),
            $texto('depa'),
        );
    }
}
