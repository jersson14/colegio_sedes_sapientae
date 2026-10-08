<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Comunicado\Comunicado;
use App\Domain\Salud\Atencion;
use App\Domain\Salud\TipoAtencion;

/** Atenciones de salud y comunicados. */
interface BienestarRepositorio
{
    public function registrarAtencion(TipoAtencion $tipo, Atencion $atencion, int $profesional): void;

    /** @return bool false si no existe o es de otro tipo */
    public function modificarAtencion(TipoAtencion $tipo, int $id, Atencion $atencion): bool;

    /** @return int 1 publicado, 2 duplicado */
    public function registrarComunicado(Comunicado $comunicado, string $imagen, int $autor): int;

    /** @return bool false si no existe */
    public function modificarComunicado(int $id, Comunicado $comunicado, string $imagen): bool;

    /** Imagen guardada, o null si el comunicado no existe. */
    public function imagenDeComunicado(int $id): ?string;

    public function eliminarComunicado(int $id): bool;
}
