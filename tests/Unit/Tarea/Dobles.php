<?php

declare(strict_types=1);

namespace Tests\Unit\Tarea;

use App\Domain\Tarea\Actividad;
use App\Repositories\TareaRepositorio;
use App\Support\AlmacenDocumentos;

/** Registra qué carpetas se guardaron y borraron, sin tocar el disco. */
final class AlmacenDocumentosFalso implements AlmacenDocumentos
{
    /** @var list<string> */
    public array $guardadas = [];
    /** @var list<string> */
    public array $borradas = [];
    /** @var list<array{tmp: string, nombre: string}> */
    public array $recibidos = [['tmp' => '/tmp/x', 'nombre' => 'X.PDF']];

    public function validar(): array
    {
        return $this->recibidos;
    }

    public function guardar(array $documentos, string $prefijo = ''): string
    {
        return $this->guardadas[] = 'controller/tareas/documentos/' . $prefijo . 'nueva';
    }

    public function borrar(string $ruta): void
    {
        $this->borradas[] = $ruta;
    }
}

/** Responde lo que se le indique en $respuesta (código o éxito). */
final class TareaRepositorioFalso implements TareaRepositorio
{
    public int $respuesta = 1;
    public ?string $carpeta = 'controller/tareas/documentos/vieja';

    public function publicar(Actividad $tarea, string $carpeta): int
    {
        return $this->respuesta;
    }

    public function modificar(string $id, Actividad $tarea, string $carpeta): int
    {
        return $this->respuesta;
    }

    public function carpetaDeTarea(string $id): ?string
    {
        return $this->carpeta;
    }

    public function eliminar(string $id): bool
    {
        return $this->respuesta === 1;
    }

    public function finalizar(string $id): bool
    {
        return true;
    }

    public function entregar(int $detalle, string $carpeta, bool $reemplazo): bool
    {
        return $this->respuesta === 1;
    }

    public function calificar(int $detalle, int $nota, string $observacion): bool
    {
        return true;
    }

    public function registrarExamen(Actividad $examen): int
    {
        return 1;
    }

    public function modificarExamen(string $id, Actividad $examen): int
    {
        return 1;
    }

    public function estadoExamen(string $id, string $estado): bool
    {
        return true;
    }

    public function eliminarExamen(string $id): void
    {
    }
}
