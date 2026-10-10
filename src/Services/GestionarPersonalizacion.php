<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Empresa\ColorInstitucion;
use App\Domain\Empresa\PaginaPublica;
use App\Repositories\EmpresaRepositorio;

/** Color y página pública de la institución (Fase 4.7), editados por su administrador. */
final class GestionarPersonalizacion
{
    public function __construct(private readonly EmpresaRepositorio $empresa)
    {
    }

    /** @return array<string, string> los campos del formulario «Personalizar» */
    public function formulario(): array
    {
        $actual = $this->empresa->personalizacion() ?? ['color' => null, 'pagina' => null];
        return ['color' => $actual['color'] ?? ''] + (PaginaPublica::desdeJson($actual['pagina']) ?? new PaginaPublica())->aFormulario();
    }

    /**
     * @param array<string, mixed> $formulario
     * @throws \InvalidArgumentException con un mensaje para el usuario si algún campo no es válido
     */
    public function guardar(array $formulario): bool
    {
        $color = ColorInstitucion::desdeTexto((string) ($formulario['color'] ?? ''));
        $pagina = PaginaPublica::desdeFormulario($formulario);
        // Una página sin ningún texto equivale a no tener página propia (NULL: la de por defecto).
        $vacia = $pagina->json() === (new PaginaPublica())->json();
        return $this->empresa->guardarPersonalizacion($color?->hex, $vacia ? null : $pagina->json());
    }
}
