<?php

declare(strict_types=1);

namespace App\Comercial;

use App\Tenancy\EstadoTenant;
use PDO;

/** Planes y suscripciones en la BD maestra (Fase 4B.1). */
final class PdoRepositorioComercial
{
    public function __construct(private readonly PDO $maestro, private readonly int $diasExportacion = 30)
    {
    }

    public function condiciones(string $slug): ?Condiciones
    {
        $consulta = $this->maestro->prepare(
            'SELECT t.estado, t.prueba_hasta, t.suspendido_desde, p.codigo, p.nombre, p.max_alumnos, p.max_usuarios,
                    p.max_almacenamiento_mb, p.precio_mensual, p.precio_por_alumno, p.moneda
               FROM tenants t
               LEFT JOIN suscripciones s ON s.tenant_id = t.id AND s.vigente = 1
               LEFT JOIN planes p ON p.id = s.plan_id
              WHERE t.slug = ?'
        );
        $consulta->execute([$slug]);
        $f = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($f)) {
            return null;
        }
        $suspendido = $f['suspendido_desde'] !== null ? new \DateTimeImmutable((string) $f['suspendido_desde']) : null;
        return new Condiciones(
            EstadoTenant::from((string) $f['estado']),
            $f['codigo'] !== null ? self::desdeFila($f) : null,
            $f['prueba_hasta'] !== null ? new \DateTimeImmutable((string) $f['prueba_hasta']) : null,
            $suspendido?->modify("+{$this->diasExportacion} days"),
        );
    }

    /** @return list<Plan> */
    public function planes(): array
    {
        return array_values(array_map(self::desdeFila(...), $this->maestro->query('SELECT * FROM planes WHERE activo = 1 ORDER BY codigo')->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function plan(string $codigo): ?Plan
    {
        $consulta = $this->maestro->prepare('SELECT * FROM planes WHERE codigo = ? AND activo = 1');
        $consulta->execute([$codigo]);
        $f = $consulta->fetch(PDO::FETCH_ASSOC);
        return is_array($f) ? self::desdeFila($f) : null;
    }

    public function guardarPlan(Plan $p): void
    {
        $this->maestro->prepare(
            'INSERT INTO planes (codigo, nombre, max_alumnos, max_usuarios, max_almacenamiento_mb, precio_mensual, precio_por_alumno, moneda)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), max_alumnos = VALUES(max_alumnos), max_usuarios = VALUES(max_usuarios),
               max_almacenamiento_mb = VALUES(max_almacenamiento_mb), precio_mensual = VALUES(precio_mensual),
               precio_por_alumno = VALUES(precio_por_alumno), moneda = VALUES(moneda), activo = 1'
        )->execute([$p->codigo, $p->nombre, $p->limite(Recurso::Alumnos), $p->limite(Recurso::Usuarios),
            $p->limite(Recurso::AlmacenamientoMb), $p->precioMensual, $p->precioPorAlumno, $p->moneda]);
    }

    /** Cierra la suscripción vigente (si hay) y abre otra con $codigo desde hoy. */
    public function asignarPlan(string $slug, string $codigo, string $ciclo = 'MENSUAL'): void
    {
        $ids = $this->maestro->prepare('SELECT (SELECT id FROM tenants WHERE slug = ?) AS tenant, (SELECT id FROM planes WHERE codigo = ? AND activo = 1) AS plan');
        $ids->execute([$slug, $codigo]);
        $fila = $ids->fetch(PDO::FETCH_ASSOC);
        if (!is_array($fila) || $fila['tenant'] === null || $fila['plan'] === null) {
            throw new \DomainException("No existe la institución «{$slug}» o el plan «{$codigo}».");
        }
        $propia = !$this->maestro->inTransaction();
        if ($propia) {
            $this->maestro->beginTransaction();
        }
        try {
            $this->maestro->prepare('UPDATE suscripciones SET vigente = 0, fin = CURDATE() WHERE tenant_id = ? AND vigente = 1')->execute([$fila['tenant']]);
            $this->maestro->prepare('INSERT INTO suscripciones (tenant_id, plan_id, inicio, ciclo) VALUES (?, ?, CURDATE(), ?)')
                ->execute([$fila['tenant'], $fila['plan'], $ciclo]);
            if ($propia) {
                $this->maestro->commit();
            }
        } catch (\Throwable $e) {
            if ($propia) {
                $this->maestro->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $f */
    private static function desdeFila(array $f): Plan
    {
        $entero = static fn (mixed $v): ?int => $v === null ? null : (int) $v;
        return new Plan(
            (string) $f['codigo'],
            (string) $f['nombre'],
            [
                Recurso::Alumnos->value => $entero($f['max_alumnos']),
                Recurso::Usuarios->value => $entero($f['max_usuarios']),
                Recurso::AlmacenamientoMb->value => $entero($f['max_almacenamiento_mb']),
            ],
            $f['precio_mensual'] !== null ? (string) $f['precio_mensual'] : null,
            $f['precio_por_alumno'] !== null ? (string) $f['precio_por_alumno'] : null,
            (string) $f['moneda'],
        );
    }
}
