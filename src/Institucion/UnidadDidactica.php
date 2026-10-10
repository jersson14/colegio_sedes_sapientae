<?php

declare(strict_types=1);

namespace App\Institucion;

/** Una unidad didáctica del plan de estudios (Fase 5.3), validada antes de llegar a la BD. */
final class UnidadDidactica
{
    public function __construct(
        public readonly int $id,
        public readonly int $modulo,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly int $periodoAcademico,
        public readonly string $creditos,
        public readonly int $horasTeoricas,
        public readonly int $horasPracticas,
        public readonly bool $activa = true,
    ) {
        $errores = [];
        if (preg_match('/^[A-Z0-9][A-Z0-9-]{1,19}$/', $codigo) !== 1) {
            $errores[] = 'código: 2 a 20 letras mayúsculas, dígitos o guiones';
        }
        if (trim($nombre) === '' || mb_strlen($nombre) > 200) {
            $errores[] = 'nombre: obligatorio, máx. 200 caracteres';
        }
        if ($periodoAcademico < 1 || $periodoAcademico > 10) {
            $errores[] = 'periodo académico: de I a X';
        }
        if (preg_match('/^\d{1,2}(\.\d)?$/', $creditos) !== 1 || (float) $creditos <= 0 || (float) $creditos > 30) {
            $errores[] = 'créditos: mayor que 0 y hasta 30, con un decimal como máximo (p. ej. 2.5)';
        }
        foreach (['horas teóricas' => $horasTeoricas, 'horas prácticas' => $horasPracticas] as $campo => $horas) {
            if ($horas < 0 || $horas > 999) {
                $errores[] = "$campo: de 0 a 999";
            }
        }
        if ($horasTeoricas + $horasPracticas === 0) {
            $errores[] = 'la unidad debe tener horas teóricas o prácticas';
        }
        if ($modulo <= 0) {
            $errores[] = 'módulo formativo: obligatorio';
        }
        if ($errores !== []) {
            throw new \InvalidArgumentException('Unidad didáctica inválida: ' . implode('; ', $errores) . '.');
        }
    }

    /** @param array<string, mixed> $f campos del formulario */
    public static function desdeFormulario(array $f): self
    {
        $entero = static fn (string $c): int => preg_match('/^\d{1,4}$/', trim((string) ($f[$c] ?? ''))) === 1 ? (int) $f[$c] : -1;
        return new self(
            max(0, $entero('id')),
            $entero('modulo'),
            strtoupper(trim((string) ($f['codigo'] ?? ''))),
            trim((string) ($f['nombre'] ?? '')),
            $entero('periodo_academico'),
            str_replace(',', '.', trim((string) ($f['creditos'] ?? ''))),
            $entero('horas_teoricas'),
            $entero('horas_practicas'),
            ($f['estado'] ?? 'ACTIVO') !== 'INACTIVO',
        );
    }
}
