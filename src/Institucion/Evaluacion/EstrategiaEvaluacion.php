<?php

declare(strict_types=1);

namespace App\Institucion\Evaluacion;

/**
 * Cómo se promedian las notas de una institución (Fase 5.5). Abierto a nuevos tipos de institución sin
 * tocar a quien lo usa: el récord académico pide una estrategia y la aplica.
 */
interface EstrategiaEvaluacion
{
    /**
     * @param list<array{nota: float, creditos: float}> $calificaciones una por unidad (su último intento)
     * @return float|null null si no hay ninguna calificación
     */
    public function promedio(array $calificaciones): ?float;

    public function nombre(): string;
}
