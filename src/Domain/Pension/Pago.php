<?php

declare(strict_types=1);

namespace App\Domain\Pension;

use App\Domain\Monto;
use App\Support\Lote;
use InvalidArgumentException;

/** Un pago de una matrícula (una fila de la tabla de cobro de js/console_pago_pension.js). */
final class Pago
{
    private const CONCEPTOS = ['ADMISION', 'ALUMNO NUEVO', 'MATRICULA', 'PENSION'];

    private function __construct(
        public readonly int $matricula,
        public readonly string $concepto,
        public readonly ?int $pension,
        public readonly Monto $monto,
    ) {
    }

    /**
     * El panel envía cada campo como una lista separada por comas (una posición por fila).
     *
     * @param array<string, mixed> $post
     * @return list<self>
     * @throws InvalidArgumentException si las listas no cuadran o una fila es inválida
     */
    public static function listaDesdeFormulario(array $post): array
    {
        $columnas = array_map(
            static fn (string $campo): array => explode(',', (string) ($post[$campo] ?? '')),
            ['id_matri', 'concepto', 'id_pension', 'monto'],
        );
        $filas = count($columnas[0]);
        foreach ($columnas as $columna) {
            if (count($columna) !== $filas) {
                throw new InvalidArgumentException('Los datos del cobro no cuadran');
            }
        }
        $pagos = [];
        for ($i = 0; $i < $filas; $i++) {
            $concepto = strtoupper(trim($columnas[1][$i]));
            if (!in_array($concepto, self::CONCEPTOS, true)) {
                throw new InvalidArgumentException('Concepto de pago no válido');
            }
            $pension = trim($columnas[2][$i]);
            $pagos[] = new self(
                Lote::idPositivo(trim($columnas[0][$i]), 'matrícula'),
                $concepto,
                $concepto === 'PENSION' || $pension !== '' ? Lote::idPositivo($pension, 'pensión') : null,
                Monto::desde(trim($columnas[3][$i]), 'monto'),
            );
        }
        return $pagos;
    }
}
