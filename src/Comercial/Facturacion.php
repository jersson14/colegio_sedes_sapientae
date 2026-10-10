<?php

declare(strict_types=1);

namespace App\Comercial;

use PDO;

/**
 * Cobros a las instituciones (Fase 4B.4 y 4B.5), en la BD maestra.
 *
 * Son COBROS INTERNOS (lo que se le cobra a cada colegio y si pagó), no comprobantes electrónicos: la
 * factura o boleta válida ante SUNAT se emite aparte, con un OSE/PSE autorizado.
 *
 *   - registrarConsumo(): la foto diaria de lo que usa cada colegio (para cobrar por alumno).
 *   - generar(): un cobro por periodo y suscripción vigente con precio (no en PRUEBA, SUSPENDIDO ni CANCELADO).
 *   - marcarMorosos(): ACTIVO con un cobro vencido hace más de $diasGracia → MOROSO (consulta, no da altas).
 *   - registrarPago(): PAGADA y, si ya no debe nada vencido, MOROSO → ACTIVO.
 */
final class Facturacion
{
    public function __construct(
        private readonly PDO $maestro,
        private readonly int $diasPago = 10,
        private readonly int $diasGracia = 5,
    ) {
    }

    /** @param array<string, int> $resumen por Recurso::value */
    public function registrarConsumo(string $slug, array $resumen, \DateTimeImmutable $fecha): void
    {
        $this->maestro->prepare(
            'INSERT INTO consumos (tenant_id, fecha, alumnos, usuarios, almacenamiento_mb)
             SELECT id, ?, ?, ?, ? FROM tenants WHERE slug = ?
             ON DUPLICATE KEY UPDATE alumnos = VALUES(alumnos), usuarios = VALUES(usuarios), almacenamiento_mb = VALUES(almacenamiento_mb)'
        )->execute([$fecha->format('Y-m-d'), $resumen[Recurso::Alumnos->value] ?? 0, $resumen[Recurso::Usuarios->value] ?? 0,
            $resumen[Recurso::AlmacenamientoMb->value] ?? 0, $slug]);
    }

    public function tieneConsumo(string $slug, \DateTimeImmutable $fecha): bool
    {
        $consulta = $this->maestro->prepare('SELECT 1 FROM consumos c JOIN tenants t ON t.id = c.tenant_id WHERE t.slug = ? AND c.fecha = ?');
        $consulta->execute([$slug, $fecha->format('Y-m-d')]);
        return $consulta->fetchColumn() !== false;
    }

    /** @return list<string> los números de cobro creados */
    public function generar(\DateTimeImmutable $hoy): array
    {
        $suscripciones = $this->maestro->query(
            "SELECT s.id AS suscripcion, s.ciclo, t.id AS tenant, t.slug, p.codigo, p.nombre, p.precio_mensual, p.precio_por_alumno, p.moneda,
                    (SELECT c.alumnos FROM consumos c WHERE c.tenant_id = t.id ORDER BY c.fecha DESC LIMIT 1) AS alumnos
               FROM suscripciones s
               JOIN tenants t ON t.id = s.tenant_id
               JOIN planes p ON p.id = s.plan_id
              WHERE s.vigente = 1 AND t.estado IN ('ACTIVO', 'MOROSO')
                AND (p.precio_mensual IS NOT NULL OR p.precio_por_alumno IS NOT NULL)"
        )->fetchAll(PDO::FETCH_ASSOC);
        $creados = [];
        foreach ($suscripciones as $s) {
            $anual = $s['ciclo'] === 'ANUAL';
            $inicio = $anual ? $hoy->setDate((int) $hoy->format('Y'), 1, 1) : $hoy->modify('first day of this month');
            $fin = $anual ? $hoy->setDate((int) $hoy->format('Y'), 12, 31) : $hoy->modify('last day of this month');
            $existe = $this->maestro->prepare('SELECT 1 FROM facturas WHERE tenant_id = ? AND periodo_inicio = ?');
            $existe->execute([$s['tenant'], $inicio->format('Y-m-d')]);
            if ($existe->fetchColumn() !== false) {
                continue;
            }
            $plan = new Plan(
                (string) $s['codigo'],
                (string) $s['nombre'],
                [],
                $s['precio_mensual'] !== null ? (string) $s['precio_mensual'] : null,
                $s['precio_por_alumno'] !== null ? (string) $s['precio_por_alumno'] : null,
                (string) $s['moneda']
            );
            $alumnos = (int) ($s['alumnos'] ?? 0);
            $mensual = (int) str_replace('.', '', (string) $plan->importeMensual($alumnos));
            $centimos = $anual ? $mensual * 12 : $mensual;
            $numero = $this->siguienteNumero($hoy);
            $this->maestro->prepare(
                'INSERT INTO facturas (tenant_id, suscripcion_id, numero, periodo_inicio, periodo_fin, emision, vencimiento, monto, moneda, detalle)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $s['tenant'], $s['suscripcion'], $numero, $inicio->format('Y-m-d'), $fin->format('Y-m-d'), $hoy->format('Y-m-d'),
                $hoy->modify("+{$this->diasPago} days")->format('Y-m-d'), sprintf('%d.%02d', intdiv($centimos, 100), $centimos % 100),
                $plan->moneda, "Plan {$plan->nombre}" . ($plan->precioPorAlumno !== null ? ", $alumnos alumnos activos" : '') . ($anual ? ', anual' : ''),
            ]);
            $creados[] = $numero;
        }
        return $creados;
    }

    /** @return list<string> las instituciones que pasan a MOROSO */
    public function marcarMorosos(\DateTimeImmutable $hoy): array
    {
        $limite = $hoy->modify("-{$this->diasGracia} days")->format('Y-m-d');
        $consulta = $this->maestro->prepare(
            "SELECT DISTINCT t.slug FROM tenants t JOIN facturas f ON f.tenant_id = t.id
              WHERE t.estado = 'ACTIVO' AND f.estado = 'PENDIENTE' AND f.vencimiento < ?"
        );
        $consulta->execute([$limite]);
        $slugs = array_map('strval', $consulta->fetchAll(PDO::FETCH_COLUMN));
        foreach ($slugs as $slug) {
            $this->maestro->prepare("UPDATE tenants SET estado = 'MOROSO' WHERE slug = ? AND estado = 'ACTIVO'")->execute([$slug]);
        }
        return $slugs;
    }

    /**
     * @return array{slug: string, reactivado: bool}
     * @throws \DomainException si el cobro no existe o no está pendiente
     */
    public function registrarPago(string $numero, string $medio, string $referencia, \DateTimeImmutable $hoy): array
    {
        $fila = $this->cobro($numero);
        if ($fila['estado'] !== 'PENDIENTE') {
            throw new \DomainException("El cobro {$numero} está {$fila['estado']}.");
        }
        $this->maestro->prepare("UPDATE facturas SET estado = 'PAGADA', pagada_en = NOW(), medio_pago = ?, referencia_pago = ? WHERE numero = ?")
            ->execute([mb_substr($medio, 0, 40), mb_substr($referencia, 0, 100), $numero]);
        return ['slug' => $fila['slug'], 'reactivado' => $this->reactivarSiAlDia($fila['slug'], $hoy)];
    }

    /** @return array{slug: string, reactivado: bool} */
    public function anular(string $numero, \DateTimeImmutable $hoy): array
    {
        $fila = $this->cobro($numero);
        if ($fila['estado'] !== 'PENDIENTE') {
            throw new \DomainException("El cobro {$numero} está {$fila['estado']}.");
        }
        $this->maestro->prepare("UPDATE facturas SET estado = 'ANULADA' WHERE numero = ?")->execute([$numero]);
        return ['slug' => $fila['slug'], 'reactivado' => $this->reactivarSiAlDia($fila['slug'], $hoy)];
    }

    /**
     * El cobro pendiente más urgente de una institución, si vence en menos de una semana o ya venció.
     *
     * @return array{numero: string, monto: string, moneda: string, vencimiento: string, vencido: bool}|null
     */
    public function aviso(string $slug, \DateTimeImmutable $hoy): ?array
    {
        $consulta = $this->maestro->prepare(
            "SELECT f.numero, f.monto, f.moneda, f.vencimiento FROM facturas f JOIN tenants t ON t.id = f.tenant_id
              WHERE t.slug = ? AND f.estado = 'PENDIENTE' AND f.vencimiento <= ? ORDER BY f.vencimiento LIMIT 1"
        );
        $consulta->execute([$slug, $hoy->modify('+7 days')->format('Y-m-d')]);
        $f = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($f)) {
            return null;
        }
        return ['numero' => (string) $f['numero'], 'monto' => (string) $f['monto'], 'moneda' => (string) $f['moneda'],
            'vencimiento' => (string) $f['vencimiento'], 'vencido' => (string) $f['vencimiento'] < $hoy->format('Y-m-d')];
    }

    /** @return list<array<string, string|null>> lo más reciente primero */
    public function recientes(int $limite = 50): array
    {
        $consulta = $this->maestro->prepare(
            'SELECT f.numero, t.slug, f.periodo_inicio, f.periodo_fin, f.emision, f.vencimiento, f.monto, f.moneda, f.detalle, f.estado,
                    f.pagada_en, f.medio_pago, f.referencia_pago
               FROM facturas f JOIN tenants t ON t.id = f.tenant_id ORDER BY f.id DESC LIMIT ?'
        );
        $consulta->bindValue(1, $limite, PDO::PARAM_INT);
        $consulta->execute();
        /** @var list<array<string, string|null>> */
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{slug: string, estado: string} */
    private function cobro(string $numero): array
    {
        $consulta = $this->maestro->prepare('SELECT t.slug, f.estado FROM facturas f JOIN tenants t ON t.id = f.tenant_id WHERE f.numero = ?');
        $consulta->execute([$numero]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($fila)) {
            throw new \DomainException("No existe el cobro {$numero}.");
        }
        return ['slug' => (string) $fila['slug'], 'estado' => (string) $fila['estado']];
    }

    /** MOROSO → ACTIVO si ya no le queda ningún cobro vencido más allá de la gracia. */
    private function reactivarSiAlDia(string $slug, \DateTimeImmutable $hoy): bool
    {
        $consulta = $this->maestro->prepare(
            "SELECT COUNT(*) FROM facturas f JOIN tenants t ON t.id = f.tenant_id
              WHERE t.slug = ? AND f.estado = 'PENDIENTE' AND f.vencimiento < ?"
        );
        $consulta->execute([$slug, $hoy->modify("-{$this->diasGracia} days")->format('Y-m-d')]);
        if ((int) $consulta->fetchColumn() > 0) {
            return false;
        }
        $reactivar = $this->maestro->prepare("UPDATE tenants SET estado = 'ACTIVO' WHERE slug = ? AND estado = 'MOROSO'");
        $reactivar->execute([$slug]);
        return $reactivar->rowCount() === 1;
    }

    /** C-AAAA-000001, correlativo por año. */
    private function siguienteNumero(\DateTimeImmutable $hoy): string
    {
        $anio = $hoy->format('Y');
        $consulta = $this->maestro->prepare('SELECT COUNT(*) FROM facturas WHERE numero LIKE ?');
        $consulta->execute(["C-$anio-%"]);
        return sprintf('C-%s-%06d', $anio, (int) $consulta->fetchColumn() + 1);
    }
}
