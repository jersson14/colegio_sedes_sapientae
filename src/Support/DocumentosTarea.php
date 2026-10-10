<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * AlmacenDocumentos sobre core/subidas.php para las tareas.
 *
 * La BD guarda «controller/tareas/documentos/<carpeta>». Las carpetas nuevas van al almacén de la
 * institución (Fase 4.6); las anteriores siguen donde estaban (tarea_carpeta_fisica()). Se descargan
 * siempre por controller/tareas/controlador_descargar_tarea.php.
 */
final class DocumentosTarea implements AlmacenDocumentos
{
    public const RUTA_BD = 'controller/tareas/documentos/';

    public function __construct(private readonly string $campo = 'archivos')
    {
        require_once __DIR__ . '/../../core/subidas.php';
    }

    public function validar(): array
    {
        /** @var list<array{tmp: string, nombre: string}> */
        return documentos_validados($this->campo);
    }

    public function guardar(array $documentos, string $prefijo = ''): string
    {
        // Único aunque dos entregas lleguen en el mismo segundo (antes compartían carpeta y una
        // reentrega borraba los archivos de otro alumno).
        $carpeta = $prefijo . time() . '_' . bin2hex(random_bytes(4));
        $fisica = tarea_carpeta_nueva($carpeta);
        if (!mkdir($fisica, 0755, true)) {
            throw new RuntimeException("No se pudo crear la carpeta $carpeta");
        }
        foreach ($documentos as $doc) {
            if (!move_uploaded_file($doc['tmp'], $fisica . '/' . $doc['nombre'])) {
                $this->borrar(self::RUTA_BD . $carpeta);
                throw new RuntimeException('No se pudo guardar ' . $doc['nombre']);
            }
        }
        return self::RUTA_BD . $carpeta;
    }

    public function borrar(string $ruta): void
    {
        $carpeta = carpeta_tarea_valida($ruta);
        $fisica = $carpeta !== null ? tarea_carpeta_fisica($carpeta) : null;
        if ($fisica === null) {
            return;
        }
        foreach (glob($fisica . '/*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        @rmdir($fisica);
    }
}
