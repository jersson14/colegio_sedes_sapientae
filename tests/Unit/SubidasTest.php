<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/subidas.php';

/**
 * Subidas (Fase 0.3): nombres de documentos y carpetas que llegan del cliente.
 */
final class SubidasTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function nombresValidos(): array
    {
        return [
            'nombre simple'           => ['tarea.pdf', 'TAREA.PDF'],
            'conserva tildes y eñe'   => ['Matemática año.docx', 'MATEMÁTICA AÑO.DOCX'],
            'traversal ../ se quita'  => ['../../Mi Tarea & cia.pdf', 'MI TAREA _ CIA.PDF'],
            'traversal con \\'        => ['..\\..\\model\\x.pdf', 'X.PDF'],
            'doble extensión aplanada' => ['x.php.pdf', 'X_PHP.PDF'],
            'extensión en mayúsculas' => ['FOTO.JPG', 'FOTO.JPG'],
            'base vacía'              => ['.pdf', 'ARCHIVO.PDF'],
            'solo símbolos'           => ['%%%.pdf', 'ARCHIVO.PDF'],
        ];
    }

    #[DataProvider('nombresValidos')]
    public function testNombreSeguro(string $original, string $esperado): void
    {
        $usados = [];
        self::assertSame($esperado, nombre_documento_seguro($original, $usados));
    }

    /** @return array<string, array{string}> */
    public static function nombresRechazados(): array
    {
        return [
            'php'                => ['shell.php'],
            'php en mayúsculas'  => ['SHELL.PHP'],
            'phtml'              => ['x.phtml'],
            '.htaccess'          => ['.htaccess'],
            'sin extensión'      => ['README'],
            'pdf.php'            => ['tarea.pdf.php'],
            'exe'                => ['programa.exe'],
        ];
    }

    #[DataProvider('nombresRechazados')]
    public function testExtensionNoPermitidaDevuelveNull(string $original): void
    {
        $usados = [];
        self::assertNull(nombre_documento_seguro($original, $usados));
    }

    public function testNombresRepetidosSeNumeran(): void
    {
        $usados = [];
        self::assertSame('X_PHP.PDF', nombre_documento_seguro('x.php.pdf', $usados));
        self::assertSame('X_PHP_2.PDF', nombre_documento_seguro('x.php.pdf', $usados));
        self::assertSame('X_PHP_3.PDF', nombre_documento_seguro('X.PHP.PDF', $usados));
    }

    public function testNombreLargoSeRecorta(): void
    {
        $usados = [];
        $nombre = nombre_documento_seguro(str_repeat('a', 300) . '.pdf', $usados);
        self::assertSame(100 + strlen('.PDF'), mb_strlen((string) $nombre));
    }

    /** @return array<string, array{string, ?string}> */
    public static function carpetas(): array
    {
        return [
            'ruta guardada en BD'    => ['controller/tareas/documentos/tarea_alumnos_1742062671', 'tarea_alumnos_1742062671'],
            'formato antiguo'        => ['1729359503', '1729359503'],
            'punto punto'            => ['..', null],
            'traversal'              => ['../../model', null],
            'traversal camuflado'    => ['tarea_alumnos_1/../x', null],
            'vacía'                  => ['', null],
            'timestamp demasiado corto' => ['tarea_alumnos_123', null],
        ];
    }

    #[DataProvider('carpetas')]
    public function testCarpetaTareaValida(string $ruta, ?string $esperado): void
    {
        self::assertSame($esperado, carpeta_tarea_valida($ruta));
    }
}
