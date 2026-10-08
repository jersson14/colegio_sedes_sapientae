<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Caja\Movimiento;
use App\Repositories\CajaRepositorio;
use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/**
 * Anulación de ingresos y egresos. Quién anula lo decide el controlador (usuario de la sesión), nunca
 * el formulario; el motivo es obligatorio y se guarda como el resto de los formularios (escapado y
 * en MAYÚSCULAS).
 */
final class AnularMovimiento
{
    public function __construct(private readonly CajaRepositorio $caja)
    {
    }

    /**
     * @return bool false si no existe o ya estaba anulado
     * @throws InvalidArgumentException si el id o el motivo no son válidos
     */
    public function ejecutar(Movimiento $tipo, mixed $id, mixed $motivo, int $anuladoPor): bool
    {
        $motivo = Texto::deFormulario($motivo);
        Texto::exigirLargo('motivo de la anulación', $motivo, 255, obligatorio: true);
        if ($anuladoPor <= 0) {
            throw new InvalidArgumentException('Sin usuario que anule');
        }
        return $this->caja->anular($tipo, Lote::idPositivo($id, 'movimiento'), $motivo, $anuladoPor);
    }
}
