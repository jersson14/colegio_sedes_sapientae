<?php

declare(strict_types=1);

namespace App\Domain\Asistencia;

/** Valores del ENUM asistencia.estado (antes, un valor fuera de la lista se guardaba vacío). */
enum EstadoAsistencia: string
{
    case Presente = 'PRESENTE';
    case Tarde = 'TARDE';
    case Ausente = 'AUSENTE';
    case Justificado = 'JUSTIFICADO';
}
