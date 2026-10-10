<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\RespaldoInstitucion;
use PHPUnit\Framework\TestCase;

/** Lo que se comprueba de un respaldo antes de restaurar nada (Fase 4.10). */
final class RespaldoManifiestoTest extends TestCase
{
    private string $carpeta;

    protected function setUp(): void
    {
        $this->carpeta = sys_get_temp_dir() . '/respaldo_prueba_' . getmypid();
        @mkdir($this->carpeta);
        file_put_contents("$this->carpeta/base.sql", "CREATE TABLE x (id INT);\n");
        file_put_contents("$this->carpeta/archivos.zip", 'zip');
        $this->manifiesto();
    }

    protected function tearDown(): void
    {
        RespaldoInstitucion::borrarCarpeta($this->carpeta);
    }

    /** @param array<string, mixed> $cambios */
    private function manifiesto(array $cambios = []): void
    {
        file_put_contents("$this->carpeta/manifiesto.json", (string) json_encode($cambios + [
            'formato' => RespaldoInstitucion::VERSION_FORMATO,
            'slug' => 'colegio-a',
            'base_datos' => 'sge_colegio_a',
            'filas' => ['x' => 0],
            'archivos_almacen' => 0,
            'sha256' => ['base.sql' => hash_file('sha256', "$this->carpeta/base.sql"), 'archivos.zip' => hash_file('sha256', "$this->carpeta/archivos.zip")],
        ]));
    }

    public function testUnRespaldoIntegroSeAcepta(): void
    {
        self::assertSame('colegio-a', RespaldoInstitucion::manifiesto($this->carpeta)['slug']);
    }

    public function testUnArchivoModificadoSeRechaza(): void
    {
        file_put_contents("$this->carpeta/base.sql", "DROP DATABASE otra;\n", FILE_APPEND);
        $this->expectExceptionMessage('base.sql está dañado');
        RespaldoInstitucion::manifiesto($this->carpeta);
    }

    public function testUnArchivoQueFaltaSeRechaza(): void
    {
        unlink("$this->carpeta/archivos.zip");
        $this->expectExceptionMessage('archivos.zip está dañado');
        RespaldoInstitucion::manifiesto($this->carpeta);
    }

    public function testSinManifiestoOConOtroFormatoNoEsUnRespaldo(): void
    {
        $this->manifiesto(['formato' => 99]);
        $this->expectExceptionMessage('no es un respaldo válido');
        RespaldoInstitucion::manifiesto($this->carpeta);
    }
}
