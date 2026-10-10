<?php

declare(strict_types=1);

namespace App\Superadmin;

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
    ) {
    }

    /**
     * @return list<array{slug: string, razon_social: string, tipo: string, estado: string, base_datos: string,
     *     dominio: ?string, fecha_alta: string, alumnos: ?int, usuarios: ?int, migracion: ?string, error: ?string}>
     */
    public function listar(): array
    {
        $filas = $this->maestro->query('SELECT slug, razon_social, tipo, estado, base_datos, dominio, fecha_alta FROM tenants ORDER BY slug')
            ->fetchAll(PDO::FETCH_ASSOC);
        $lista = [];
        foreach ($filas as $f) {
            $cifras = ['alumnos' => null, 'usuarios' => null, 'migracion' => null, 'error' => null];
            try {
                $pdo = ($this->conectar)((string) $f['base_datos']);
                $cifras['alumnos'] = (int) $pdo->query("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'")->fetchColumn();
                $cifras['usuarios'] = (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE usu_estatus = 'ACTIVO'")->fetchColumn();
                $cifras['migracion'] = (string) $pdo->query('SELECT MAX(version) FROM phinxlog')->fetchColumn();
            } catch (\PDOException) {
                // Una base que no responde no debe tumbar el panel: se marca y se sigue.
                $cifras['error'] = 'La base no responde';
            }
            $lista[] = [
                'slug' => (string) $f['slug'],
                'razon_social' => (string) $f['razon_social'],
                'tipo' => (string) $f['tipo'],
                'estado' => (string) $f['estado'],
                'base_datos' => (string) $f['base_datos'],
                'dominio' => $f['dominio'] !== null ? (string) $f['dominio'] : null,
                'fecha_alta' => (string) $f['fecha_alta'],
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
        $this->maestro->prepare('UPDATE tenants SET estado = ? WHERE slug = ?')->execute([$estado->value, $slug]);
        $this->auditoria->registrar($actor, 'ESTADO', $slug, "$antes → {$estado->value}", $ip);
        return true;
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
