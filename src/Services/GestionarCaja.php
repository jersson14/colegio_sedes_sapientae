<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Caja\Movimiento;
use App\Domain\Caja\MovimientoDiverso;
use App\Repositories\CajaRepositorio;
use App\Support\Lote;
use InvalidArgumentException;

/**
 * Ingresos y egresos diversos. El responsable (quién cobra o paga) lo fija el controlador con el
 * usuario de la sesión; editar no lo cambia. Para anular: AnularMovimiento.
 */
final class GestionarCaja
{
    public function __construct(private readonly CajaRepositorio $caja)
    {
    }

    /**
     * @param array<string, mixed> $post
     * @return bool false si el indicador no es del tipo del movimiento
     * @throws InvalidArgumentException
     */
    public function registrar(Movimiento $tipo, array $post, int $responsable): bool
    {
        if ($responsable <= 0) {
            throw new InvalidArgumentException('Sin usuario responsable');
        }
        return $this->caja->registrar($tipo, MovimientoDiverso::desdeFormulario($post), $responsable);
    }

    /**
     * @param array<string, mixed> $post
     * @return bool false si no se puede editar (ver CajaRepositorio::modificar)
     * @throws InvalidArgumentException
     */
    public function modificar(Movimiento $tipo, array $post): bool
    {
        return $this->caja->modificar($tipo, Lote::idPositivo($post['id'] ?? null, 'movimiento'), MovimientoDiverso::desdeFormulario($post));
    }

    /**
     * @return bool false si el indicador está en uso o no existe (antes: 500)
     * @throws InvalidArgumentException
     */
    public function eliminarIndicador(mixed $id): bool
    {
        return $this->caja->eliminarIndicador(Lote::idPositivo($id, 'indicador'));
    }
}
