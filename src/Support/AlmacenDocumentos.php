<?php

declare(strict_types=1);

namespace App\Support;

/** Carpetas de documentos de tareas y entregas. Validar antes de tocar la BD; borrar lo viejo, después. */
interface AlmacenDocumentos
{
    /**
     * Valida los archivos recibidos (responde 422 y corta si alguno no se admite).
     *
     * @return list<array{tmp: string, nombre: string}>
     */
    public function validar(): array;

    /**
     * Guarda los documentos en una carpeta nueva y única.
     *
     * @param list<array{tmp: string, nombre: string}> $documentos
     * @return string ruta tal como se guarda en la BD
     */
    public function guardar(array $documentos, string $prefijo = ''): string;

    /** Borra una carpeta guardada antes (solo si tiene la forma que genera el sistema). */
    public function borrar(string $ruta): void;
}
