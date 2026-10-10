<?php

declare(strict_types=1);

namespace App\Domain\Empresa;

/** Color principal de una institución, «#rrggbb» en minúsculas. Va a CSS: solo ese formato. */
final class ColorInstitucion
{
    private function __construct(public readonly string $hex)
    {
    }

    /** Vacío = sin color propio (null). */
    public static function desdeTexto(string $valor): ?self
    {
        $valor = strtolower(trim($valor));
        if ($valor === '') {
            return null;
        }
        if (preg_match('/^#[0-9a-f]{6}$/', $valor) !== 1) {
            throw new \InvalidArgumentException('El color debe tener el formato #rrggbb.');
        }
        return new self($valor);
    }

    /** El mismo tono, más oscuro (bordes y estados «hover»). */
    public function oscuro(float $factor = 0.8): string
    {
        $canales = array_map(static fn (string $c): int => (int) round(hexdec($c) * $factor), str_split(substr($this->hex, 1), 2));
        return '#' . implode('', array_map(static fn (int $c): string => str_pad(dechex(max(0, min(255, $c))), 2, '0', STR_PAD_LEFT), $canales));
    }
}
