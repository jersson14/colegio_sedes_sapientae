<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PdoBienestarRepositorio;
use App\Support\FotosSubidas;
use PDO;

final class FabricaBienestar
{
    public const CARPETA_FOTOS = 'controller/comunicados/fotos';

    public static function gestionar(PDO $pdo): GestionarBienestar
    {
        return new GestionarBienestar(new PdoBienestarRepositorio($pdo), new FotosSubidas(self::CARPETA_FOTOS, self::CARPETA_FOTOS . '/'));
    }
}
