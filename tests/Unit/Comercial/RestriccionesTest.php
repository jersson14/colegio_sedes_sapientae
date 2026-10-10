<?php

declare(strict_types=1);

namespace Tests\Unit\Comercial;

use App\Comercial\Condiciones;
use App\Comercial\Plan;
use App\Comercial\Recurso;
use App\Comercial\Restricciones;
use App\Tenancy\EstadoTenant;
use PHPUnit\Framework\TestCase;

final class RestriccionesTest extends TestCase
{
    private const ALTA_ALUMNO = 'controller/alumnos/controlador_registrar_alumno.php';
    private const ALTA_DOCENTE = 'controller/docentes/controlador_registrar_docente.php';
    private const LISTADO = 'controller/alumnos/controlador_listar_alumnos.php';

    private static function plan(?int $alumnos = null, ?int $usuarios = null): Plan
    {
        return new Plan('BASICO', 'Básico', [Recurso::Alumnos->value => $alumnos, Recurso::Usuarios->value => $usuarios]);
    }

    /** @param array<string, int> $uso */
    private static function evaluar(string $ruta, Condiciones $c, array $uso = []): ?array
    {
        return Restricciones::evaluar($ruta, $c, static fn (Recurso $r): int => $uso[$r->value] ?? 0);
    }

    public function testActivoDentroDelLimitePuedeDarAltas(): void
    {
        $c = new Condiciones(EstadoTenant::Activo, self::plan(100));
        self::assertNull(self::evaluar(self::ALTA_ALUMNO, $c, ['alumnos' => 99]));
    }

    public function testElLimiteDelPlanFrenaLaAlta(): void
    {
        $c = new Condiciones(EstadoTenant::Activo, self::plan(100, 10));
        $alumno = self::evaluar(self::ALTA_ALUMNO, $c, ['alumnos' => 100]);
        self::assertSame(402, $alumno['codigo'] ?? null);
        self::assertStringContainsString('100 alumnos activos', $alumno['mensaje'] ?? '');
        self::assertSame(402, self::evaluar(self::ALTA_DOCENTE, $c, ['usuarios' => 10])['codigo'] ?? null, 'un docente es un usuario');
        self::assertNull(self::evaluar(self::LISTADO, $c, ['alumnos' => 500]), 'consultar nunca se limita');
    }

    public function testSinPlanOSinLimiteNoHayTope(): void
    {
        self::assertNull(self::evaluar(self::ALTA_ALUMNO, new Condiciones(EstadoTenant::Activo), ['alumnos' => 99999]));
        self::assertNull(self::evaluar(self::ALTA_ALUMNO, new Condiciones(EstadoTenant::Activo, self::plan()), ['alumnos' => 99999]));
    }

    public function testMorosoConsultaPeroNoDaAltas(): void
    {
        $c = new Condiciones(EstadoTenant::Moroso, self::plan());
        self::assertSame(402, self::evaluar(self::ALTA_ALUMNO, $c)['codigo'] ?? null);
        self::assertStringContainsString('pago pendiente', self::evaluar(self::ALTA_ALUMNO, $c)['mensaje'] ?? '');
        self::assertNull(self::evaluar(self::LISTADO, $c));
        self::assertNull(self::evaluar('view/MPDF/REPORTE/kardex.php', $c), 'los reportes siguen');
    }

    public function testSuspendidoSoloExporta(): void
    {
        $c = new Condiciones(EstadoTenant::Suspendido);
        self::assertSame(403, self::evaluar(self::LISTADO, $c)['codigo'] ?? null);
        self::assertSame(403, self::evaluar('view/MPDF/REPORTE/kardex.php', $c)['codigo'] ?? null);
        self::assertNull(self::evaluar(Restricciones::EXPORTACION, $c));
    }

    public function testElUsoSoloSeCalculaSiHaceFalta(): void
    {
        $calculos = 0;
        Restricciones::evaluar(self::LISTADO, new Condiciones(EstadoTenant::Activo, self::plan(1)), static function () use (&$calculos): int {
            $calculos++;
            return 0;
        });
        self::assertSame(0, $calculos, 'un listado no cuenta alumnos');
    }

    public function testModoUnicoSinRestricciones(): void
    {
        $c = Condiciones::sinRestricciones();
        self::assertTrue($c->permiteAltas());
        self::assertFalse($c->esPrueba());
        self::assertNull($c->limite(Recurso::Alumnos));
    }

    public function testImporteDelPlanEnCentimosExactos(): void
    {
        self::assertNull(self::plan()->importeMensual(300), 'sin precio no hay importe');
        self::assertSame('150.00', (new Plan('PA', 'A', [], '150'))->importeMensual(10));
        self::assertSame('237.27', (new Plan('PB', 'B', [], '100.10', '0.43'))->importeMensual(319), '100.10 + 0.43 × 319');
        self::assertSame('0.30', (new Plan('PC', 'C', [], null, '0.1'))->importeMensual(3), 'sin errores de coma flotante');
    }

    /** @return array<string, array{\Closure(): Plan}> */
    public static function planesInvalidos(): array
    {
        return [
            'código en minúsculas' => [static fn (): Plan => new Plan('basico', 'B')],
            'sin nombre' => [static fn (): Plan => new Plan('PB', ' ')],
            'precio con coma' => [static fn (): Plan => new Plan('PB', 'B', [], '10,50')],
            'límite negativo' => [static fn (): Plan => new Plan('PB', 'B', [Recurso::Alumnos->value => -1])],
            'moneda' => [static fn (): Plan => new Plan('PB', 'B', [], null, null, 'soles')],
        ];
    }

    /** @param \Closure(): Plan $crear */
    #[\PHPUnit\Framework\Attributes\DataProvider('planesInvalidos')]
    public function testPlanInvalido(\Closure $crear): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $crear();
    }
}
