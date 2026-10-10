<?php

declare(strict_types=1);

namespace App\Comercial;

/**
 * Un plan comercial (Fase 4B.1). Precios y límites son datos del panel, no código: un límite null es
 * «sin límite»; un precio null, «no se cobra por ese concepto».
 */
final class Plan
{
    /** @param array<string, ?int> $limites por Recurso::value */
    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly array $limites = [],
        public readonly ?string $precioMensual = null,
        public readonly ?string $precioPorAlumno = null,
        public readonly string $moneda = 'PEN',
    ) {
        if (preg_match('/^[A-Z0-9_]{2,30}$/', $codigo) !== 1) {
            throw new \InvalidArgumentException('Código de plan: 2 a 30 mayúsculas, dígitos o guion bajo.');
        }
        if (trim($nombre) === '') {
            throw new \InvalidArgumentException('El plan necesita un nombre.');
        }
        foreach ([$precioMensual, $precioPorAlumno] as $precio) {
            if ($precio !== null && preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precio) !== 1) {
                throw new \InvalidArgumentException("Precio inválido: «{$precio}» (p. ej. 150 o 12.50).");
            }
        }
        foreach ($limites as $limite) {
            if ($limite !== null && $limite < 0) {
                throw new \InvalidArgumentException('Un límite no puede ser negativo.');
            }
        }
        if (preg_match('/^[A-Z]{3}$/', $moneda) !== 1) {
            throw new \InvalidArgumentException('Moneda: código ISO de 3 letras (PEN, USD).');
        }
    }

    public function limite(Recurso $recurso): ?int
    {
        return $this->limites[$recurso->value] ?? null;
    }

    /** Importe de un periodo mensual con $alumnos activos (null si el plan no tiene precio). */
    public function importeMensual(int $alumnos): ?string
    {
        if ($this->precioMensual === null && $this->precioPorAlumno === null) {
            return null;
        }
        // En céntimos para no arrastrar errores de coma flotante.
        $centimos = self::centimos($this->precioMensual) + self::centimos($this->precioPorAlumno) * $alumnos;
        return sprintf('%d.%02d', intdiv($centimos, 100), $centimos % 100);
    }

    private static function centimos(?string $precio): int
    {
        if ($precio === null || $precio === '') {
            return 0;
        }
        $partes = explode('.', $precio, 2);
        return (int) $partes[0] * 100 + (int) str_pad(substr($partes[1] ?? '', 0, 2), 2, '0');
    }
}
