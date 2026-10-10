<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Comercial\PdoRepositorioComercial;
use App\Comercial\Plan;
use App\Comercial\Recurso;
use App\Tenancy\EstadoTenant;
use PDO;
use PHPUnit\Framework\TestCase;

/** Planes y suscripciones en la BD maestra (Fase 4B.1). */
final class ComercialTest extends TestCase
{
    private PDO $maestro;

    protected function setUp(): void
    {
        $nombre = getenv('MAESTRO_DB_NAME') ?: '';
        if ($nombre === '' || (getenv('DB_NAME') ?: '') === '') {
            self::markTestSkipped('Sin BD maestra: define MAESTRO_DB_NAME.');
        }
        $this->maestro = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s', getenv('DB_HOST') ?: 'localhost', (int) (getenv('DB_PORT') ?: 3306), $nombre),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $this->maestro->beginTransaction();
        $this->maestro->exec("INSERT INTO tenants (slug, razon_social, base_datos, estado) VALUES ('prueba-comercial', 'Comercial', 'prueba_comercial', 'ACTIVO')");
    }

    protected function tearDown(): void
    {
        if (isset($this->maestro) && $this->maestro->inTransaction()) {
            $this->maestro->rollBack();
        }
    }

    public function testSinPlanNoHayLimites(): void
    {
        $c = (new PdoRepositorioComercial($this->maestro))->condiciones('prueba-comercial');
        self::assertNotNull($c);
        self::assertSame(EstadoTenant::Activo, $c->estado);
        self::assertNull($c->plan);
        self::assertNull($c->limite(Recurso::Alumnos));
    }

    public function testElPlanSeGuardaSeAsignaYSeCambiaSinPerderElHistorial(): void
    {
        $repo = new PdoRepositorioComercial($this->maestro);
        $repo->guardarPlan(new Plan('PRUEBA_INT_A', 'A', [Recurso::Alumnos->value => 100], '150.50'));
        $repo->guardarPlan(new Plan('PRUEBA_INT_B', 'B', [Recurso::Alumnos->value => 300], null, '1.20'));

        $repo->asignarPlan('prueba-comercial', 'PRUEBA_INT_A');
        $a = $repo->condiciones('prueba-comercial');
        self::assertNotNull($a);
        self::assertSame(100, $a->limite(Recurso::Alumnos));
        self::assertSame('150.50', $a->plan?->precioMensual);

        $repo->asignarPlan('prueba-comercial', 'PRUEBA_INT_B');
        self::assertSame(300, $repo->condiciones('prueba-comercial')?->limite(Recurso::Alumnos));
        $historial = $this->maestro->query("SELECT COUNT(*), SUM(vigente) FROM suscripciones s JOIN tenants t ON t.id = s.tenant_id WHERE t.slug = 'prueba-comercial'")
            ->fetch(PDO::FETCH_NUM);
        self::assertSame([2, 1], array_map('intval', (array) $historial), 'la suscripción anterior se cierra, no se borra');

        // Guardar con el mismo código modifica el plan (y lo ven sus colegios).
        $repo->guardarPlan(new Plan('PRUEBA_INT_B', 'B', [Recurso::Alumnos->value => null]));
        self::assertNull($repo->condiciones('prueba-comercial')?->limite(Recurso::Alumnos));
    }

    public function testLaVentanaDeExportacionSeCuentaDesdeLaSuspension(): void
    {
        $this->maestro->exec("UPDATE tenants SET estado = 'SUSPENDIDO', suspendido_desde = '2026-03-01 10:00:00' WHERE slug = 'prueba-comercial'");
        $c = (new PdoRepositorioComercial($this->maestro, 30))->condiciones('prueba-comercial');
        self::assertNotNull($c);
        self::assertTrue($c->soloExportacion());
        self::assertSame('2026-03-31', $c->exportacionHasta?->format('Y-m-d'));
    }

    public function testPlanOColegioInexistente(): void
    {
        $this->expectException(\DomainException::class);
        (new PdoRepositorioComercial($this->maestro))->asignarPlan('prueba-comercial', 'NO_EXISTE');
    }
}
