<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PdoTareaRepositorio;
use App\Support\DocumentosTarea;
use PDO;

final class FabricaTareas
{
    public static function gestionar(PDO $pdo): GestionarTareas
    {
        return new GestionarTareas(new PdoTareaRepositorio($pdo), new DocumentosTarea());
    }
}
