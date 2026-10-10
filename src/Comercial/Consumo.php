<?php

declare(strict_types=1);

namespace App\Comercial;

use PDO;

/** Lo que una institución usa de su plan (Fase 4B.4). */
final class Consumo
{
    public function __construct(private readonly PDO $pdo, private readonly string $almacen)
    {
    }

    public function de(Recurso $recurso): int
    {
        return match ($recurso) {
            Recurso::Alumnos => (int) $this->pdo->query("SELECT COUNT(*) FROM alumnos WHERE alum_estatus = 'SI'")->fetchColumn(),
            Recurso::Usuarios => (int) $this->pdo->query("SELECT COUNT(*) FROM usuario WHERE usu_estatus = 'ACTIVO'")->fetchColumn(),
            Recurso::AlmacenamientoMb => (int) ceil(self::bytes($this->almacen) / 1048576),
        };
    }

    public static function bytes(string $carpeta): int
    {
        if (!is_dir($carpeta)) {
            return 0;
        }
        $total = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS)) as $f) {
            /** @var \SplFileInfo $f */
            $total += $f->isFile() ? (int) $f->getSize() : 0;
        }
        return $total;
    }

    /** @return array<string, int> todo junto, por Recurso::value */
    public function resumen(): array
    {
        $resumen = [];
        foreach (Recurso::cases() as $r) {
            $resumen[$r->value] = $this->de($r);
        }
        return $resumen;
    }
}
