<?php

declare(strict_types=1);

namespace App\Domain\Caja;

use App\Domain\Monto;
use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/** Un ingreso o egreso que no viene de un pago de pensión (formularios de ingresos y egresos). */
final class MovimientoDiverso
{
    private function __construct(
        public readonly int $indicador,
        public readonly int $cantidad,
        public readonly Monto $monto,
        public readonly string $observacion,
    ) {
    }

    /**
     * @param array<string, mixed> $post claves de js/console_ingresos.js / console_egresos.js
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $observacion = Texto::deFormulario($post['obse'] ?? $post['obser'] ?? '');
        Texto::exigirLargo('observación', $observacion, 255);
        return new self(
            Lote::idPositivo($post['indi'] ?? null, 'indicador'),
            Lote::idPositivo($post['cantidad'] ?? null, 'cantidad'),
            Monto::desde($post['monto'] ?? '', 'monto'),
            $observacion,
        );
    }
}
