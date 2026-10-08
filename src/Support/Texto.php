<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/** Reglas de texto de los formularios heredados (alumnos, matrícula). */
final class Texto
{
    /**
     * Normalización heredada de los formularios: escapado HTML y MAYÚSCULAS. Los datos ya guardados
     * están así y los listados los muestran sin volver a escapar, por eso se conserva.
     */
    public static function deFormulario(mixed $valor): string
    {
        return strtoupper(htmlspecialchars(trim((string) $valor), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * El largo se valida en lugar de dejar que la columna lo trunque sin avisar (CHAR(8) del DNI).
     * Se cuenta en caracteres sobre el valor ya normalizado, que es lo que se guarda.
     *
     * @throws InvalidArgumentException
     */
    public static function exigirLargo(string $campo, string $valor, int $maximo, bool $obligatorio = false): void
    {
        if ($obligatorio && $valor === '') {
            throw new InvalidArgumentException("Falta {$campo}");
        }
        if (mb_strlen($valor) > $maximo) {
            throw new InvalidArgumentException("{$campo}: máximo {$maximo} caracteres");
        }
    }
}
