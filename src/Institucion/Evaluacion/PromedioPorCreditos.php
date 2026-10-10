<?php

declare(strict_types=1);

namespace App\Institucion\Evaluacion;

/** Promedio ponderado: cada nota pesa según los créditos de su unidad (institutos). */
final class PromedioPorCreditos implements EstrategiaEvaluacion
{
    public function promedio(array $calificaciones): ?float
    {
        $creditos = array_sum(array_column($calificaciones, 'creditos'));
        if ($creditos <= 0) {
            return null;
        }
        $ponderado = array_sum(array_map(static fn (array $c): float => $c['nota'] * $c['creditos'], $calificaciones));
        return round($ponderado / $creditos, 2);
    }

    public function nombre(): string
    {
        return 'Promedio ponderado por créditos';
    }
}
