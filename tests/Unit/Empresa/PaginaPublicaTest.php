<?php

declare(strict_types=1);

namespace Tests\Unit\Empresa;

use App\Domain\Empresa\ColorInstitucion;
use App\Domain\Empresa\PaginaPublica;
use App\Repositories\EmpresaRepositorio;
use App\Services\GestionarPersonalizacion;
use PHPUnit\Framework\TestCase;

final class PaginaPublicaTest extends TestCase
{
    /** @return array<string, string> */
    private function formulario(array $cambios = []): array
    {
        return $cambios + [
            'lema' => 'Colegio Parroquial',
            'bienvenida' => 'Educamos con valores',
            'nosotros_titulo' => 'Quiénes somos',
            'nosotros_texto' => "Primer párrafo.\r\n\r\nSegundo párrafo.",
            'caracteristicas' => "Aulas amplias\n\n  Biblioteca  \n",
            'niveles' => "Primaria | Del 1.º al 6.º grado\nSecundaria",
            'valores' => 'Respeto | Con todos',
            'cifras' => "500 | Estudiantes\n30|Docentes",
            'horario' => 'Lunes a viernes',
        ];
    }

    public function testInterpretaElFormularioUnaLineaPorElemento(): void
    {
        $p = PaginaPublica::desdeFormulario($this->formulario());

        self::assertSame(['Aulas amplias', 'Biblioteca'], $p->caracteristicas, 'sin líneas vacías ni espacios');
        self::assertSame([['nombre' => 'Primaria', 'texto' => 'Del 1.º al 6.º grado'], ['nombre' => 'Secundaria', 'texto' => '']], $p->niveles);
        self::assertSame([['valor' => 500, 'etiqueta' => 'Estudiantes'], ['valor' => 30, 'etiqueta' => 'Docentes']], $p->cifras);
        self::assertSame("Primer párrafo.\n\nSegundo párrafo.", $p->nosotrosTexto);
    }

    public function testIdaYVueltaPorJsonYFormulario(): void
    {
        $p = PaginaPublica::desdeFormulario($this->formulario());
        $de_nuevo = PaginaPublica::desdeJson($p->json());

        self::assertNotNull($de_nuevo);
        self::assertSame($p->json(), $de_nuevo->json());
        self::assertSame($p->json(), PaginaPublica::desdeFormulario($de_nuevo->aFormulario())->json());
    }

    public function testElTextoSeGuardaTalCualYSeEscapaAlMostrarse(): void
    {
        $p = PaginaPublica::desdeFormulario($this->formulario(['lema' => '<script>alert(1)</script>']));
        self::assertSame('<script>alert(1)</script>', $p->lema);
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidos(): array
    {
        return [
            'cifra sin número' => [['cifras' => 'muchos | Estudiantes']],
            'cifra sin etiqueta' => [['cifras' => '500']],
            'demasiadas características' => [['caracteristicas' => implode("\n", range(1, 9))]],
            'lema largo' => [['lema' => str_repeat('x', 81)]],
            'valor largo' => [['valores' => 'Respeto | ' . str_repeat('x', 301)]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidos')]
    public function testRechazaConUnMotivoLegible(array $cambios): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaginaPublica::desdeFormulario($this->formulario($cambios));
    }

    public function testJsonIlegibleEsComoNoTenerPagina(): void
    {
        self::assertNull(PaginaPublica::desdeJson(null));
        self::assertNull(PaginaPublica::desdeJson('{roto'));
        self::assertNull(PaginaPublica::desdeJson('{"lema":"' . str_repeat('x', 200) . '"}'));
    }

    public function testColor(): void
    {
        self::assertNull(ColorInstitucion::desdeTexto(''));
        self::assertSame('#1f4e79', ColorInstitucion::desdeTexto(' #1F4E79 ')?->hex);
        self::assertSame('#1a3167', ColorInstitucion::desdeTexto('#1f3a79')?->oscuro(0.85) ?? '', 'más oscuro, mismo formato');
        $this->expectException(\InvalidArgumentException::class);
        ColorInstitucion::desdeTexto('red;}body{display:none');
    }

    public function testElServicioGuardaNullSiLaPaginaQuedaVacia(): void
    {
        $repo = new class () implements EmpresaRepositorio {
            /** @var array{color: ?string, pagina: ?string} */
            public array $guardado = ['color' => 'x', 'pagina' => 'x'];

            /** @return array{color: ?string, pagina: ?string} */
            public function personalizacion(): array
            {
                return $this->guardado;
            }

            public function guardarPersonalizacion(?string $color, ?string $paginaJson): bool
            {
                $this->guardado = ['color' => $color, 'pagina' => $paginaJson];
                return true;
            }
        };
        $servicio = new GestionarPersonalizacion($repo);

        self::assertTrue($servicio->guardar(['color' => '']));
        self::assertSame(['color' => null, 'pagina' => null], $repo->guardado);

        $servicio->guardar(['color' => '#AABBCC', 'lema' => 'Hola']);
        self::assertSame('#aabbcc', $repo->guardado['color']);
        self::assertSame('Hola', $servicio->formulario()['lema']);
        self::assertSame('#aabbcc', $servicio->formulario()['color']);
    }
}
