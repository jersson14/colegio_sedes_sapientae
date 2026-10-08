<?php

declare(strict_types=1);

namespace App\Support;

/**
 * AlmacenFotos sobre core/subidas.php (validación por contenido, nombre generado por el servidor,
 * borrado acotado a la carpeta). Una foto inválida responde 422 y corta, antes de tocar la BD.
 */
final class FotosSubidas implements AlmacenFotos
{
    /**
     * @param string $carpeta  relativa a la raíz del proyecto, p. ej. 'controller/alumnos/fotos'
     * @param string $sinFoto  ruta que se guarda cuando no hay foto
     */
    public function __construct(
        private readonly string $carpeta,
        private readonly string $sinFoto,
        private readonly string $campo = 'foto',
    ) {
        require_once __DIR__ . '/../../core/subidas.php';
    }

    public function validarNueva(): string
    {
        return $this->carpeta . '/' . imagen_validada($this->campo);
    }

    public function guardar(string $ruta): bool
    {
        return imagen_guardar($this->campo, $this->carpeta, basename($ruta));
    }

    public function borrar(string $ruta): void
    {
        borrar_archivo_subido($ruta, $this->carpeta);
    }

    public function rutaSinFoto(): string
    {
        return $this->sinFoto;
    }
}
