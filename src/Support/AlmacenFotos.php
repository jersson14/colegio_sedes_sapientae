<?php

declare(strict_types=1);

namespace App\Support;

/** Fotos subidas en una carpeta. Validar antes de tocar la BD; guardar y borrar, después. */
interface AlmacenFotos
{
    /** Valida la foto recibida y devuelve la ruta (relativa a la raíz) que tendrá al guardarse. */
    public function validarNueva(): string;

    /** Mueve a su ruta la foto validada. */
    public function guardar(string $ruta): bool;

    /** Borra una foto anterior; no hace nada con las imágenes por defecto ni fuera de la carpeta. */
    public function borrar(string $ruta): void;

    /** Ruta que se guarda cuando no hay foto. */
    public function rutaSinFoto(): string;
}
