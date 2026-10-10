<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/subidas.php';

/**
 * Almacén de archivos por institución (Fase 4.6). tests/bootstrap.php fija ALMACEN_DIR en una carpeta
 * temporal; el modo es el único (slug «principal»), así que la ubicación anterior también cuenta.
 */
final class AlmacenInstitucionTest extends TestCase
{
    private const FOTOS = 'controller/alumnos/fotos';

    /** @var list<string> */
    private array $creados = [];

    private function crear(string $ruta, string $contenido = 'x'): string
    {
        @mkdir(dirname($ruta), 0700, true);
        file_put_contents($ruta, $contenido);
        $this->creados[] = $ruta;
        return $ruta;
    }

    protected function tearDown(): void
    {
        foreach ($this->creados as $ruta) {
            if (is_dir($ruta)) {
                array_map('unlink', glob($ruta . '/*') ?: []);
                @rmdir($ruta);
            } else {
                @unlink($ruta);
            }
        }
    }

    public function testElAlmacenEsPorInstitucionYFueraDelProyecto(): void
    {
        self::assertSame(dirname(config_ruta_env()) . DIRECTORY_SEPARATOR . 'almacen/principal', almacen_raiz());
    }

    public function testEncuentraLoNuevoEnElAlmacen(): void
    {
        $fisica = $this->crear(almacen_raiz() . '/' . self::FOTOS . '/IMG_nueva.png');
        self::assertSame(realpath($fisica), subida_ubicar(self::FOTOS . '/IMG_nueva.png', self::FOTOS));
    }

    public function testEnModoUnicoSigueEncontrandoLoSubidoAntes(): void
    {
        $fisica = $this->crear(SUBIDA_RAIZ . '/' . self::FOTOS . '/IMG_prueba_antigua.png');
        self::assertSame(realpath($fisica), subida_ubicar(self::FOTOS . '/IMG_prueba_antigua.png', self::FOTOS));
    }

    public function testNoSaleDeLaCarpetaPedida(): void
    {
        $this->crear(almacen_raiz() . '/controller/secreto.txt');
        self::assertNull(subida_ubicar(self::FOTOS . '/../../secreto.txt', self::FOTOS));
        self::assertNull(subida_ubicar(self::FOTOS . '/&#46;&#46;/&#46;&#46;/secreto.txt', self::FOTOS));
        self::assertNull(subida_ubicar('controller/secreto.txt', self::FOTOS), 'el archivo existe, pero fuera de la carpeta');
    }

    public function testBorraEnElAlmacen(): void
    {
        $fisica = $this->crear(almacen_raiz() . '/' . self::FOTOS . '/IMG_borrar.png');
        borrar_archivo_subido(self::FOTOS . '/IMG_borrar.png', self::FOTOS);
        self::assertFileDoesNotExist($fisica);
    }

    public function testCarpetasDeTareaNuevasYAntiguas(): void
    {
        $nueva = tarea_carpeta_nueva('1700000000_abcdef12');
        self::assertSame(almacen_raiz() . '/controller/tareas/documentos/1700000000_abcdef12', $nueva);
        $this->crear($nueva . '/A.PDF');
        $this->creados[] = $nueva;
        self::assertSame(realpath($nueva), tarea_carpeta_fisica('1700000000_abcdef12'));

        $antigua = SUBIDA_RAIZ . '/controller/tareas/controller/tareas/documentos/1600000000';
        $this->crear($antigua . '/B.PDF');
        $this->creados[] = $antigua;
        self::assertSame(realpath($antigua), tarea_carpeta_fisica('1600000000'));
        self::assertNull(tarea_carpeta_fisica('1500000000'));
    }
}
