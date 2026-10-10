<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Empresa\PaginaPublica;
use App\Repositories\PdoEmpresaRepositorio;

/** SP_OBTENER/MODIFICAR_PERSONALIZACION (migración 20261022000000, Fase 4.7). */
final class EmpresaRepositorioTest extends BaseDatosTestCase
{
    public function testGuardaYLeeColorYPagina(): void
    {
        $repo = new PdoEmpresaRepositorio($this->pdo);
        $pagina = new PaginaPublica(lema: 'Colegio Ñandú', caracteristicas: ['Piscina'], cifras: [['valor' => 300, 'etiqueta' => 'Alumnos']]);

        self::assertTrue($repo->guardarPersonalizacion('#123abc', $pagina->json()));
        $leido = $repo->personalizacion();

        self::assertSame('#123abc', $leido['color'] ?? null);
        self::assertSame($pagina->json(), PaginaPublica::desdeJson($leido['pagina'] ?? null)?->json(), 'el JSON con tildes y eñes vuelve igual');
    }

    public function testQuitarLaPersonalizacionDejaNull(): void
    {
        $repo = new PdoEmpresaRepositorio($this->pdo);
        $repo->guardarPersonalizacion('#123abc', '{"lema":"x"}');
        $repo->guardarPersonalizacion(null, null);

        self::assertSame(['color' => null, 'pagina' => null], $repo->personalizacion());
    }

    public function testSinEmpresaDevuelveCero(): void
    {
        $this->pdo->exec('DELETE FROM empresa');
        $repo = new PdoEmpresaRepositorio($this->pdo);

        self::assertNull($repo->personalizacion());
        self::assertFalse($repo->guardarPersonalizacion('#123abc', null));
    }
}
