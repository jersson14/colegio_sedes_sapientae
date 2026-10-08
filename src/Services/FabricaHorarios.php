<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PdoHorarioRepositorio;
use PDO;

final class FabricaHorarios
{
    public static function gestionar(PDO $pdo): GestionarHorarios
    {
        return new GestionarHorarios(new PdoHorarioRepositorio($pdo));
    }
}
