<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/subidas.php';

/**
 * borrar_archivo_subido() recibe la "foto actual" desde el cliente o la BD:
 * solo puede borrar dentro de su carpeta y nunca imágenes por defecto (Fase 0.3).
 */
final class BorrarArchivoSubidoTest extends TestCase
{
    private const DIR = 'storage/prueba_borrado';
    private string $abs;

    protected function setUp(): void
    {
        $this->abs = SUBIDA_RAIZ . '/' . self::DIR;
        @mkdir($this->abs . '/fotos', 0700, true);
        file_put_contents($this->abs . '/canario.txt', 'no borrar');
        file_put_contents($this->abs . '/fotos/IMG_legitima.png', 'x');
        file_put_contents($this->abs . '/fotos/VACIO.png', 'x');
    }

    protected function tearDown(): void
    {
        foreach (['fotos/IMG_legitima.png', 'fotos/VACIO.png', 'canario.txt'] as $f) {
            @unlink($this->abs . '/' . $f);
        }
        @rmdir($this->abs . '/fotos');
        @rmdir($this->abs);
    }

    public function testBorraUnaFotoLegitimaDeSuCarpeta(): void
    {
        borrar_archivo_subido(self::DIR . '/fotos/IMG_legitima.png', self::DIR . '/fotos');
        self::assertFileDoesNotExist($this->abs . '/fotos/IMG_legitima.png');
    }

    public function testNoSaleDeLaCarpetaConPuntoPunto(): void
    {
        borrar_archivo_subido(self::DIR . '/fotos/../canario.txt', self::DIR . '/fotos');
        self::assertFileExists($this->abs . '/canario.txt');
    }

    public function testNoSaleDeLaCarpetaConEntidadesHtml(): void
    {
        // El valor llega escapado con htmlspecialchars desde el controlador.
        borrar_archivo_subido(self::DIR . '/fotos/&#46;&#46;/canario.txt', self::DIR . '/fotos');
        self::assertFileExists($this->abs . '/canario.txt');
    }

    public function testNuncaBorraLaImagenPorDefecto(): void
    {
        borrar_archivo_subido(self::DIR . '/fotos/VACIO.png', self::DIR . '/fotos');
        self::assertFileExists($this->abs . '/fotos/VACIO.png');
    }

    public function testRutaInexistenteNoFalla(): void
    {
        borrar_archivo_subido(self::DIR . '/fotos/no_existe.png', self::DIR . '/fotos');
        self::assertFileExists($this->abs . '/canario.txt');
    }
}
