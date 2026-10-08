<?php

declare(strict_types=1);

namespace App\Domain\Pension;

use App\Domain\Monto;
use App\Support\Lote;
use DateTimeImmutable;
use InvalidArgumentException;

/** Pensión de un nivel para un mes (formulario de js/console_pensiones.js). */
final class DatosPension
{
    private const MESES = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO',
        'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE']; // ENUM pensiones.mes

    private function __construct(
        public readonly int $nivel,
        public readonly string $mes,
        public readonly string $vencimiento,
        public readonly Monto $precio,
        public readonly Monto $mora,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $mes = strtoupper(trim((string) ($post['mes'] ?? '')));
        if (!in_array($mes, self::MESES, true)) {
            throw new InvalidArgumentException('Mes no válido');
        }
        $vencimiento = trim((string) ($post['fecha'] ?? ''));
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $vencimiento);
        if ($fecha === false || $fecha->format('Y-m-d') !== $vencimiento) {
            throw new InvalidArgumentException('Fecha de vencimiento no válida');
        }
        return new self(
            Lote::idPositivo($post['nivel'] ?? null, 'nivel'),
            $mes,
            $vencimiento,
            Monto::desde($post['precio'] ?? '', 'precio'),
            Monto::desde($post['mora'] ?? '', 'mora'),
        );
    }
}
