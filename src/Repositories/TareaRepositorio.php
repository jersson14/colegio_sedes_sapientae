<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Tarea\Actividad;

interface TareaRepositorio
{
    /** @return int 1 publicada, 2 ya hay una con ese tema hoy en el curso */
    public function publicar(Actividad $tarea, string $carpeta): int;

    /** @return int 1 modificada, 2 choca con otra del curso */
    public function modificar(string $id, Actividad $tarea, string $carpeta): int;

    /** Carpeta del enunciado, o null si la tarea no existe. */
    public function carpetaDeTarea(string $id): ?string;

    /** @return bool false si tiene entregas enviadas o calificadas, o no existe */
    public function eliminar(string $id): bool;

    /** Finaliza la tarea; quien no entregó queda calificado con 5. @return bool false si no existe */
    public function finalizar(string $id): bool;

    /**
     * @param bool $reemplazo true si cambia una entrega ya enviada
     * @return bool false si la tarea venció o finalizó, o la entrega ya está calificada
     */
    public function entregar(int $detalle, string $carpeta, bool $reemplazo): bool;

    /** @return bool false si la entrega no existe */
    public function calificar(int $detalle, int $nota, string $observacion): bool;

    /** Exámenes */
    public function registrarExamen(Actividad $examen): int;

    public function modificarExamen(string $id, Actividad $examen): int;

    public function estadoExamen(string $id, string $estado): bool;

    public function eliminarExamen(string $id): void;
}
