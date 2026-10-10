<?php

declare(strict_types=1);

namespace App\Institucion\Evaluacion;

/** Todas las asignaturas pesan igual (colegios). */
final class PromedioSimple implements EstrategiaEvaluacion
{
    public function promedio(array $calificaciones): ?float
    {
        if ($calificaciones === []) {
            return null;
        }
        return round(array_sum(array_column($calificaciones, 'nota')) / count($calificaciones), 2);
    }

    public function nombre(): string
    {
        return 'Promedio simple';
    }
}
