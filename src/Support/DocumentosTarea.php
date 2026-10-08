<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * AlmacenDocumentos sobre core/subidas.php para las tareas.
 *
 * Las rutas de la BD («controller/tareas/documentos/<carpeta>») siempre se resolvieron relativas a
 * controller/tareas/, así que la carpeta física es controller/tareas/controller/tareas/documentos/
 * (cerrada por .htaccess; se descarga con controlador_descargar_tarea.php). Se conserva.
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
        $fisica = self::fisica($carpeta);
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
        if ($carpeta === null || !is_dir(self::fisica($carpeta))) {
            return;
        }
        foreach (glob(self::fisica($carpeta) . '/*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        @rmdir(self::fisica($carpeta));
    }

    private static function fisica(string $carpeta): string
    {
        return __DIR__ . '/../../controller/tareas/' . self::RUTA_BD . $carpeta;
    }
}
