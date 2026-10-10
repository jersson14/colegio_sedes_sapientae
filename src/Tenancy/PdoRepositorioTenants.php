<?php

declare(strict_types=1);

namespace App\Tenancy;

use PDO;

final class PdoRepositorioTenants implements RepositorioTenants
{
    private const COLUMNAS = 'slug, base_datos, estado, razon_social, suspendido_desde';

    public function __construct(private readonly PDO $maestro)
    {
    }

    public function porSlug(string $slug): ?Tenant
    {
        return $this->uno('slug = ?', $slug);
    }

    public function porDominio(string $dominio): ?Tenant
    {
        return $this->uno('dominio = ?', $dominio);
    }

    public function todos(): array
    {
        // Sin las ya borradas (Fase 4B.8): su fila se conserva, pero su base no existe y nada debe recorrerla.
        $filas = $this->maestro->query('SELECT ' . self::COLUMNAS . ' FROM tenants WHERE borrado_en IS NULL ORDER BY slug')->fetchAll(PDO::FETCH_ASSOC);
        return array_map(self::tenant(...), $filas);
    }

    private function uno(string $condicion, string $valor): ?Tenant
    {
        $consulta = $this->maestro->prepare('SELECT ' . self::COLUMNAS . " FROM tenants WHERE $condicion");
        $consulta->execute([$valor]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return is_array($fila) ? self::tenant($fila) : null;
    }

    /** @param array<string, mixed> $fila */
    private static function tenant(array $fila): Tenant
    {
        return new Tenant(
            (string) $fila['slug'],
            (string) $fila['base_datos'],
            EstadoTenant::from((string) $fila['estado']),
            (string) $fila['razon_social'],
            isset($fila['suspendido_desde']) ? new \DateTimeImmutable((string) $fila['suspendido_desde']) : null,
        );
    }
}
