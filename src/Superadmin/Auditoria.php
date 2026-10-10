<?php

declare(strict_types=1);

namespace App\Superadmin;

use PDO;

/** Registro de lo que se hace desde el panel: quién, qué, sobre qué colegio, cuándo y desde qué IP. */
final class Auditoria
{
    public function __construct(private readonly PDO $maestro)
    {
    }

    public function registrar(string $actor, string $accion, ?string $tenant, string $detalle, string $ip): void
    {
        $this->maestro->prepare('INSERT INTO auditoria (actor, accion, tenant, detalle, ip) VALUES (?, ?, ?, ?, ?)')
            ->execute([$actor, $accion, $tenant, mb_substr($detalle, 0, 500), mb_substr($ip, 0, 45)]);
    }

    /** @return list<array<string, string|null>> lo más reciente primero */
    public function recientes(int $limite = 50): array
    {
        $consulta = $this->maestro->prepare('SELECT fecha, actor, accion, tenant, detalle, ip FROM auditoria ORDER BY id DESC LIMIT ?');
        $consulta->bindValue(1, $limite, PDO::PARAM_INT);
        $consulta->execute();
        /** @var list<array<string, string|null>> */
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
