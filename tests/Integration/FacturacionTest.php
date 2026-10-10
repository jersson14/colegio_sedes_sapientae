<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Comercial\Facturacion;
use App\Comercial\PdoRepositorioComercial;
use App\Comercial\Plan;
use App\Comercial\Recurso;
use PDO;
use PHPUnit\Framework\TestCase;

/** Consumo y cobros en la BD maestra (Fase 4B.4 y 4B.5). Todo en una transacción que se revierte. */
final class FacturacionTest extends TestCase
{
    private PDO $maestro;
    private Facturacion $facturacion;
    private \DateTimeImmutable $hoy;

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
        $this->facturacion = new Facturacion($this->maestro, 10, 5);
        $this->hoy = new \DateTimeImmutable('2027-03-15');
        $repo = new PdoRepositorioComercial($this->maestro);
        $repo->guardarPlan(new Plan('PRUEBA_FACT', 'Plan facturado', [Recurso::Alumnos->value => null], '100.00', '2.00'));
        foreach (['prueba-fact-a' => 'ACTIVO', 'prueba-fact-p' => 'PRUEBA'] as $slug => $estado) {
            $this->maestro->prepare('INSERT INTO tenants (slug, razon_social, base_datos, estado) VALUES (?, ?, ?, ?)')
                ->execute([$slug, $slug, str_replace('-', '_', $slug), $estado]);
            $repo->asignarPlan($slug, 'PRUEBA_FACT');
            $this->facturacion->registrarConsumo($slug, ['alumnos' => 10, 'usuarios' => 3, 'almacenamiento_mb' => 1], $this->hoy);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->maestro) && $this->maestro->inTransaction()) {
            $this->maestro->rollBack();
        }
    }

    /** @return array<string, mixed> */
    private function cobroDe(string $slug): array
    {
        $consulta = $this->maestro->prepare('SELECT f.* FROM facturas f JOIN tenants t ON t.id = f.tenant_id WHERE t.slug = ? ORDER BY f.id DESC LIMIT 1');
        $consulta->execute([$slug]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return is_array($fila) ? $fila : [];
    }

    private function estado(string $slug): string
    {
        $consulta = $this->maestro->prepare('SELECT estado FROM tenants WHERE slug = ?');
        $consulta->execute([$slug]);
        return (string) $consulta->fetchColumn();
    }

    public function testCobraElPeriodoConElConsumoUnaSolaVezYNoDuranteLaPrueba(): void
    {
        $creados = $this->facturacion->generar($this->hoy);
        $cobro = $this->cobroDe('prueba-fact-a');

        self::assertContains($cobro['numero'], $creados);
        self::assertMatchesRegularExpression('/^C-2027-\d{6}$/', (string) $cobro['numero']);
        self::assertSame(
            ['120.00', '2027-03-01', '2027-03-31', '2027-03-25'],
            [$cobro['monto'], $cobro['periodo_inicio'], $cobro['periodo_fin'], $cobro['vencimiento']],
            '100 + 2 × 10 alumnos; vence a los 10 días'
        );
        self::assertSame([], $this->cobroDe('prueba-fact-p'), 'en PRUEBA no se cobra');
        self::assertNotContains($cobro['numero'], $this->facturacion->generar($this->hoy->modify('+3 days')), 'un periodo, un cobro');
    }

    public function testElCicloAnualCobraDoceMeses(): void
    {
        $this->maestro->exec("UPDATE suscripciones s JOIN tenants t ON t.id = s.tenant_id SET s.ciclo = 'ANUAL' WHERE t.slug = 'prueba-fact-a' AND s.vigente = 1");
        $this->facturacion->generar($this->hoy);
        $cobro = $this->cobroDe('prueba-fact-a');
        self::assertSame(['1440.00', '2027-01-01', '2027-12-31'], [$cobro['monto'], $cobro['periodo_inicio'], $cobro['periodo_fin']]);
    }

    public function testMorosoSoloPasadaLaGraciaYElPagoLoDevuelveAActivo(): void
    {
        $this->facturacion->generar($this->hoy);
        $numero = (string) $this->cobroDe('prueba-fact-a')['numero'];

        self::assertNotContains('prueba-fact-a', $this->facturacion->marcarMorosos(new \DateTimeImmutable('2027-03-30')), 'vencido, pero dentro de la gracia');
        self::assertContains('prueba-fact-a', $this->facturacion->marcarMorosos(new \DateTimeImmutable('2027-03-31')));
        self::assertSame('MOROSO', $this->estado('prueba-fact-a'));

        $aviso = $this->facturacion->aviso('prueba-fact-a', new \DateTimeImmutable('2027-03-31'));
        self::assertSame([$numero, true], [$aviso['numero'] ?? null, $aviso['vencido'] ?? null]);

        $pago = $this->facturacion->registrarPago($numero, 'Transferencia', 'OP-123', new \DateTimeImmutable('2027-04-02'));
        self::assertSame(['prueba-fact-a', true], [$pago['slug'], $pago['reactivado']]);
        self::assertSame('ACTIVO', $this->estado('prueba-fact-a'));
        self::assertSame(['PAGADA', 'Transferencia', 'OP-123'], [$this->cobroDe('prueba-fact-a')['estado'], $this->cobroDe('prueba-fact-a')['medio_pago'], $this->cobroDe('prueba-fact-a')['referencia_pago']]);
        self::assertNull($this->facturacion->aviso('prueba-fact-a', new \DateTimeImmutable('2027-04-02')), 'sin deuda, sin aviso');

        $this->expectException(\DomainException::class);
        $this->facturacion->registrarPago($numero, 'Otra vez', '', $this->hoy);
    }

    public function testElAvisoLlegaUnaSemanaAntes(): void
    {
        $this->facturacion->generar($this->hoy);
        self::assertNull($this->facturacion->aviso('prueba-fact-a', new \DateTimeImmutable('2027-03-17')), 'vence el 25: aún no');
        self::assertFalse($this->facturacion->aviso('prueba-fact-a', new \DateTimeImmutable('2027-03-18'))['vencido'] ?? true);
    }

    public function testAnularDejaAlDia(): void
    {
        $this->facturacion->generar($this->hoy);
        $this->facturacion->marcarMorosos(new \DateTimeImmutable('2027-04-10'));
        $anulado = $this->facturacion->anular((string) $this->cobroDe('prueba-fact-a')['numero'], new \DateTimeImmutable('2027-04-10'));
        self::assertTrue($anulado['reactivado']);
        self::assertSame(['ANULADA', 'ACTIVO'], [$this->cobroDe('prueba-fact-a')['estado'], $this->estado('prueba-fact-a')]);
    }

    public function testLaFotoDeConsumoSeActualizaEnElMismoDia(): void
    {
        self::assertTrue($this->facturacion->tieneConsumo('prueba-fact-a', $this->hoy));
        $this->facturacion->registrarConsumo('prueba-fact-a', ['alumnos' => 25], $this->hoy);
        $alumnos = $this->maestro->query("SELECT c.alumnos FROM consumos c JOIN tenants t ON t.id = c.tenant_id WHERE t.slug = 'prueba-fact-a'")->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame([25], array_map('intval', $alumnos), 'una fila por día');
    }
}
