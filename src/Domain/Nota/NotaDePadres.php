<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use App\Support\Lote;
use App\Support\TextoLibre;
use InvalidArgumentException;

/** Nota de los padres en una competencia (texto libre) y periodo. */
final class NotaDePadres
{
    private function __construct(
        public readonly int $matricula,
        public readonly int $periodo,
        public readonly string $competencia,
        public readonly ValorNota $nota,
    ) {
    }

    /**
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function desdeArreglo(array $r): self
    {
        $competencia = TextoLibre::desde($r['competencia'] ?? '', 'competencia');
        if ($competencia === '') {
            throw new InvalidArgumentException('Falta la competencia');
        }
        return new self(
            Lote::idPositivo($r['id_matri'] ?? null, 'matrícula'),
            Lote::idPositivo($r['perio'] ?? null, 'periodo'),
            $competencia,
            ValorNota::desde($r['nota'] ?? ''),
        );
    }

    /** @return array{id_matri: int, perio: int, competencia: string, nota: string} formato de SP_REGISTRAR_NOTAS_PADRES */
    public function paraProcedimiento(): array
    {
        return ['id_matri' => $this->matricula, 'perio' => $this->periodo,
            'competencia' => $this->competencia, 'nota' => $this->nota->valor];
    }
}
