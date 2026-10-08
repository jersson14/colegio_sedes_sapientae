<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PdoNotaRepositorio;
use PDO;

final class FabricaNotas
{
    public static function gestionar(PDO $pdo): GestionarNotas
    {
        return new GestionarNotas(new PdoNotaRepositorio($pdo));
    }
}
