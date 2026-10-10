<?php

declare(strict_types=1);

namespace App\Superadmin;

use App\Comercial\PdoRepositorioComercial;
use App\Comercial\Plan;
use App\Tenancy\AltaInstitucion;
use App\Tenancy\EstadoTenant;
use App\Tenancy\SolicitudAlta;
use PDO;

/**
 * Lo que el superadministrador hace con las instituciones (Fase 4, hito 4.8). Toda acción que cambia
 * algo queda en la auditoría, también las que fallan.
 */
final class PanelInstituciones
{
    /** @param \Closure(string): PDO $conectar abre la base de una institución (para sus cifras) */
    public function __construct(
        private readonly PDO $maestro,
        private readonly \Closure $conectar,
        private readonly Auditoria $auditoria,
        private readonly ?AltaInstitucion $alta = null,
        private readonly int $diasRetencion = 90,
    ) {
    }

    /**
     * @return list<array{slug: string, razon_social: string, tipo: string, estado: string, demo: bool, base_datos: string,
     *     dominio: ?string, fecha_alta: string, prueba_hasta: ?string, plan: ?string, max_alumnos: ?int, max_usuarios: ?int,
     *     alumnos: ?int, usuarios: ?int,
     *     migracion: ?string, error: ?string}>
     */
    public function listar(): array
    {
        $filas = $this->maestro->query(
            'SELECT t.slug, t.razon_social, t.tipo, t.estado, t.demo, t.base_datos, t.dominio, t.fecha_alta, t.prueba_hasta, t.retencion_hasta, t.borrado_en,
                    p.codigo AS plan,
                    p.max_alumnos, p.max_usuarios
               FROM tenants t
               LEFT JOIN suscripciones s ON s.tenant_id = t.id AND s.vigente = 1
               LEFT JOIN planes p ON p.id = s.plan_id
              ORDER BY t.slug'
        )->fetchAll(PDO::FETCH_ASSOC);
        $lista = [];
        foreach ($filas as $f) {
            $cifras = ['alumnos' => null, 'usuarios' => null, 'migracion' => null, 'error' => null];
            if ($f['borrado_en'] !== null) {
                $cifras['error'] = "Datos borrados el {$f['borrado_en']}";
            } elseif ($f['estado'] === 'CANCELADO' && $f['retencion_hasta'] !== null) {
                $cifras['error'] = "Cancelada: datos conservados hasta el {$f['retencion_hasta']}";
            } else {
                try {
                    $pdo = ($this->conectar)((string) $f['base_datos']);
                    $cifras['alumnos'] = (int) $pdo->query("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'")->fetchColumn();
                    $cifras['usuarios'] = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE usu_estatus = 'ACTIVO'")->fetchColumn();
                    $cifras['migracion'] = (string) $pdo->query('SELECT MAX(version) FROM phinxlog')->fetchColumn();
                } catch (\PDOException) {
                    // Una base que no responde no debe tumbar el panel: se marca y se sigue.
                    $cifras['error'] = 'La base no responde';
                }
            }
            $lista[] = [
                'slug' => (string) $f['slug'],
                'razon_social' => (string) $f['razon_social'],
                'tipo' => (string) $f['tipo'],
                'estado' => (string) $f['estado'],
                'demo' => (int) $f['demo'] === 1,
                'base_datos' => (string) $f['base_datos'],
                'dominio' => $f['dominio'] !== null ? (string) $f['dominio'] : null,
                'fecha_alta' => (string) $f['fecha_alta'],
                'prueba_hasta' => $f['prueba_hasta'] !== null ? (string) $f['prueba_hasta'] : null,
                'plan' => $f['plan'] !== null ? (string) $f['plan'] : null,
                'max_alumnos' => $f['max_alumnos'] !== null ? (int) $f['max_alumnos'] : null,
                'max_usuarios' => $f['max_usuarios'] !== null ? (int) $f['max_usuarios'] : null,
            ] + $cifras;
        }
        return $lista;
    }

    public function cambiarEstado(string $slug, EstadoTenant $estado, string $actor, string $ip): bool
    {
        $anterior = $this->maestro->prepare('SELECT estado FROM tenants WHERE slug = ?');
        $anterior->execute([$slug]);
        $antes = $anterior->fetchColumn();
        if ($antes === false) {
            throw new \DomainException("No existe la institución «{$slug}».");
        }
        // Las fechas antes que el estado: MySQL evalúa el SET en orden y debe comparar con el estado anterior.
        // suspendido_desde abre la ventana de exportación (Fase 4B.3); salir de SUSPENDIDO la cierra.
        // retencion_hasta (Fase 4B.8): hasta cuándo se conservan los datos de un colegio cancelado; después
        // tools/baja_tenant.php los puede borrar. Uno ya borrado no cambia de estado.
        $this->maestro->prepare(
            "UPDATE tenants SET
                suspendido_desde = CASE WHEN ? = 'SUSPENDIDO' THEN IF(estado = 'SUSPENDIDO', suspendido_desde, NOW()) ELSE NULL END,
                cancelado_en = CASE WHEN ? = 'CANCELADO' THEN IF(estado = 'CANCELADO', cancelado_en, NOW()) ELSE NULL END,
                retencion_hasta = CASE WHEN ? = 'CANCELADO' THEN IF(estado = 'CANCELADO', retencion_hasta, CURDATE() + INTERVAL ? DAY) ELSE NULL END,
                estado = ?
              WHERE slug = ? AND borrado_en IS NULL"
        )->execute([$estado->value, $estado->value, $estado->value, $this->diasRetencion, $estado->value, $slug]);
        $this->auditoria->registrar($actor, 'ESTADO', $slug, "$antes → {$estado->value}", $ip);
        return true;
    }

    /** @return list<Plan> */
    public function planes(): array
    {
        return (new PdoRepositorioComercial($this->maestro))->planes();
    }

    public function guardarPlan(Plan $plan, string $actor, string $ip): void
    {
        (new PdoRepositorioComercial($this->maestro))->guardarPlan($plan);
        $this->auditoria->registrar($actor, 'PLAN', null, sprintf(
            '%s «%s»: %s alumnos, %s usuarios, %s MB, %s/mes + %s/alumno %s',
            $plan->codigo,
            $plan->nombre,
            ...array_map(static fn (?string $v): string => $v ?? '—', [
                self::texto($plan->limite(\App\Comercial\Recurso::Alumnos)),
                self::texto($plan->limite(\App\Comercial\Recurso::Usuarios)),
                self::texto($plan->limite(\App\Comercial\Recurso::AlmacenamientoMb)),
                $plan->precioMensual,
                $plan->precioPorAlumno,
                $plan->moneda,
            ]),
        ), $ip);
    }

    public function asignarPlan(string $slug, string $codigo, ?string $pruebaHasta, string $actor, string $ip): void
    {
        if ($pruebaHasta !== null && \DateTimeImmutable::createFromFormat('!Y-m-d', $pruebaHasta) === false) {
            throw new \InvalidArgumentException('Fecha de fin de prueba inválida.');
        }
        (new PdoRepositorioComercial($this->maestro))->asignarPlan($slug, $codigo);
        $this->maestro->prepare('UPDATE tenants SET prueba_hasta = ? WHERE slug = ?')->execute([$pruebaHasta, $slug]);
        $this->auditoria->registrar($actor, 'PLAN_ASIGNADO', $slug, $codigo . ($pruebaHasta !== null ? ", prueba hasta $pruebaHasta" : ''), $ip);
    }

    private static function texto(?int $n): ?string
    {
        return $n === null ? null : (string) $n;
    }

    /** @return string la contraseña inicial del administrador del colegio */
    public function darDeAlta(SolicitudAlta $solicitud, string $actor, string $ip): string
    {
        if ($this->alta === null) {
            throw new \LogicException('Alta no disponible en este panel.');
        }
        try {
            $clave = $this->alta->ejecutar($solicitud);
        } catch (\Throwable $e) {
            $this->auditoria->registrar($actor, 'ALTA_FALLIDA', $solicitud->slug, $e->getMessage(), $ip);
            throw $e;
        }
        $this->auditoria->registrar($actor, 'ALTA', $solicitud->slug, "base {$solicitud->baseDatos}, estado {$solicitud->estado->value}", $ip);
        return $clave;
    }
}
