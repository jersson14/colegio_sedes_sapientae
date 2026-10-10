<?php

declare(strict_types=1);

namespace App\Repositories;

interface EmpresaRepositorio
{
    /** @return array{color: ?string, pagina: ?string}|null null si la institución no tiene empresa */
    public function personalizacion(): ?array;

    public function guardarPersonalizacion(?string $color, ?string $paginaJson): bool;
}
