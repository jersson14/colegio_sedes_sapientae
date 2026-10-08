<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PdoAlumnoRepositorio;
use App\Support\FotosSubidas;
use PDO;

/** Arma GestionarAlumnos con sus dependencias reales (lo usan los endpoints heredados). */
final class FabricaAlumnos
{
    public const CARPETA_FOTOS = 'controller/alumnos/fotos';
    /** Marca de «sin foto» que ya reconocen las vistas (muestran VACIO.png). */
    public const SIN_FOTO = self::CARPETA_FOTOS . '/';

    public static function gestionar(PDO $pdo): GestionarAlumnos
    {
        return new GestionarAlumnos(new PdoAlumnoRepositorio($pdo), new FotosSubidas(self::CARPETA_FOTOS, self::SIN_FOTO));
    }
}
