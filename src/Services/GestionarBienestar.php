<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Comunicado\Comunicado;
use App\Domain\Salud\Atencion;
use App\Domain\Salud\TipoAtencion;
use App\Repositories\BienestarRepositorio;
use App\Support\AlmacenFotos;
use App\Support\Lote;
use InvalidArgumentException;

/**
 * Atenciones de enfermería y psicología, y comunicados. El profesional que atiende y el autor de un
 * comunicado los fija el controlador con el usuario de la sesión; editar no los cambia.
 */
final class GestionarBienestar
{
    public function __construct(
        private readonly BienestarRepositorio $bienestar,
        private readonly AlmacenFotos $fotos,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function registrarAtencion(TipoAtencion $tipo, array $post, int $profesional): void
    {
        if ($profesional <= 0) {
            throw new InvalidArgumentException('Sin profesional que atienda');
        }
        $this->bienestar->registrarAtencion($tipo, Atencion::desdeFormulario($post), $profesional);
    }

    /**
     * @param array<string, mixed> $post
     * @return bool false si no existe o es de otro tipo (la enfermera no edita atenciones psicológicas)
     * @throws InvalidArgumentException
     */
    public function modificarAtencion(TipoAtencion $tipo, array $post): bool
    {
        return $this->bienestar->modificarAtencion($tipo, Lote::idPositivo($post['id'] ?? null, 'atención'), Atencion::desdeFormulario($post));
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function publicarComunicado(array $post, bool $conFoto, int $autor): int
    {
        if ($autor <= 0) {
            throw new InvalidArgumentException('Sin autor');
        }
        $comunicado = Comunicado::desdeFormulario($post);
        $imagen = $conFoto ? $this->fotos->validarNueva() : $this->fotos->rutaSinFoto();
        $resultado = $this->bienestar->registrarComunicado($comunicado, $imagen, $autor);
        if ($resultado === 1 && $conFoto) {
            $this->fotos->guardar($imagen);
        }
        return $resultado;
    }

    /**
     * La imagen actual sale de la BD, no del formulario.
     *
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function modificarComunicado(array $post, bool $fotoNueva): bool
    {
        $id = Lote::idPositivo($post['id'] ?? null, 'comunicado');
        $comunicado = Comunicado::desdeFormulario($post);
        $anterior = $this->bienestar->imagenDeComunicado($id) ?? $this->fotos->rutaSinFoto();
        $imagen = $fotoNueva ? $this->fotos->validarNueva() : $anterior;
        $modificado = $this->bienestar->modificarComunicado($id, $comunicado, $imagen);
        if ($modificado && $fotoNueva && $this->fotos->guardar($imagen)) {
            $this->fotos->borrar($anterior);
        }
        return $modificado;
    }

    /** @throws InvalidArgumentException */
    public function eliminarComunicado(mixed $id): bool
    {
        $id = Lote::idPositivo($id, 'comunicado');
        $imagen = $this->bienestar->imagenDeComunicado($id);
        $eliminado = $this->bienestar->eliminarComunicado($id);
        if ($eliminado && $imagen !== null) {
            $this->fotos->borrar($imagen); // antes la imagen quedaba en el disco
        }
        return $eliminado;
    }
}
