<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Tenancy\SolicitudAlta;
use PHPUnit\Framework\TestCase;

final class SolicitudAltaTest extends TestCase
{
    public function testLaBaseSeDerivaDelSlug(): void
    {
        $s = new SolicitudAlta('colegio-san-jose', 'Colegio San José', 'a@example.com', '12345678', 'Ana', 'Pérez');
        self::assertSame('sge_colegio_san_jose', $s->baseDatos);
        self::assertSame('admin', $s->adminUsuario);
    }

    public function testInformaTodosLosErroresALaVez(): void
    {
        try {
            new SolicitudAlta('Mal Slug', '', 'no-es-correo', '1234', '', '', 'x', 'UNIVERSIDAD', dominio: 'https://x.pe');
            self::fail('debía rechazarse');
        } catch (\InvalidArgumentException $e) {
            foreach (['slug', 'razón social', 'email', 'DNI', 'nombres', 'usuario', 'tipo', 'dominio'] as $campo) {
                self::assertStringContainsString($campo, $e->getMessage());
            }
        }
    }

    public function testSubdominiosReservados(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SolicitudAlta('maestro', 'X', 'a@example.com', '12345678', 'Ana', 'Pérez');
    }

    public function testUnaBaseQueAlteraLaSentenciaSeRechaza(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SolicitudAlta('x', 'X', 'a@example.com', '12345678', 'Ana', 'Pérez', baseDatos: 'x`; DROP DATABASE y; --');
    }
}
