<?php

declare(strict_types=1);

namespace App\Domain\Comunicado;

use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/** Comunicado general o por grado (formulario de js/console_comunicados.js). */
final class Comunicado
{
    private const TIPOS = ['GENERAL', 'POR GRADO'];
    private const ESTADOS = ['ACTIVO', 'INACTIVO'];

    private function __construct(
        public readonly string $tipo,
        public readonly int $aula,
        public readonly string $titulo,
        public readonly string $descripcion,
        public readonly string $estado,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $tipo = Texto::deFormulario($post['tipo'] ?? '');
        $estado = Texto::deFormulario($post['esta'] ?? 'ACTIVO');
        if (!in_array($tipo, self::TIPOS, true) || !in_array($estado, self::ESTADOS, true)) {
            throw new InvalidArgumentException('Tipo o estado de comunicado no válido');
        }
        $titulo = Texto::deFormulario($post['titulo'] ?? '');
        $descripcion = Texto::deFormulario($post['descripcion'] ?? '');
        // Los SP reciben VARCHAR(255): lo más largo se truncaba.
        Texto::exigirLargo('título', $titulo, 255, obligatorio: true);
        Texto::exigirLargo('descripción', $descripcion, 255);
        return new self($tipo, Lote::idPositivo($post['grado'] ?? null, 'aula'), $titulo, $descripcion, $estado);
    }
}
