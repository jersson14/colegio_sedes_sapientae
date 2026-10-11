<?php

declare(strict_types=1);

namespace App\Comercial;

use PDO;

/** Módulos opcionales por institución en la BD maestra (modo múltiple). */
final class PdoRepositorioModulos
{
    public function __construct(private readonly PDO $maestro)
    {
    }

    public function de(string $slug): ModulosInstitucion
    {
        $consulta = $this->maestro->prepare(
            'SELECT m.modulo, m.limite_mensual FROM tenant_modulos m JOIN tenants t ON t.id = m.tenant_id WHERE t.slug = ? AND m.activo = 1'
        );
        $consulta->execute([$slug]);
        $activos = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $activos[(string) $fila['modulo']] = $fila['limite_mensual'] !== null ? (int) $fila['limite_mensual'] : null;
        }
        return new ModulosInstitucion($activos);
    }

    /**
     * Todas las instituciones, para el panel.
     *
     * @return array<string, array<string, array{activo: bool, limite: ?int}>> slug => módulo => estado
     */
    public function todos(): array
    {
        $todos = [];
        $filas = $this->maestro->query('SELECT t.slug, m.modulo, m.activo, m.limite_mensual FROM tenant_modulos m JOIN tenants t ON t.id = m.tenant_id')
            ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as $f) {
            $todos[(string) $f['slug']][(string) $f['modulo']] = [
                'activo' => (bool) $f['activo'],
                'limite' => $f['limite_mensual'] !== null ? (int) $f['limite_mensual'] : null,
            ];
        }
        return $todos;
    }

    /** @throws \DomainException si la institución no existe */
    public function guardar(string $slug, ModuloOpcional $modulo, bool $activo, ?int $limite, string $actor): void
    {
        $id = $this->maestro->prepare('SELECT id FROM tenants WHERE slug = ? AND borrado_en IS NULL');
        $id->execute([$slug]);
        $tenantId = $id->fetchColumn();
        if ($tenantId === false) {
            throw new \DomainException("No existe la institución «{$slug}».");
        }
        $this->maestro->prepare(
            'INSERT INTO tenant_modulos (tenant_id, modulo, activo, limite_mensual, actualizado, actualizado_por) VALUES (?, ?, ?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE activo = VALUES(activo), limite_mensual = VALUES(limite_mensual), actualizado = NOW(), actualizado_por = VALUES(actualizado_por)'
        )->execute([$tenantId, $modulo->value, $activo ? 1 : 0, $modulo->tieneLimite() ? $limite : null, mb_substr($actor, 0, 100)]);
    }
}
