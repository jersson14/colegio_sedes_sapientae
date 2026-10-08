<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use InvalidArgumentException;

/** Una nota nueva de un alumno en un criterio y periodo (claves del JSON de js/console_notas.js). */
final class RegistroNota
{
    private function __construct(
        public readonly int $matricula,
        public readonly int $periodo,
        public readonly int $criterio,
        public readonly ValorNota $nota,
        public readonly string $conclusiones,
    ) {
    }

    /**
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function desdeArreglo(array $r): self
    {
        return new self(
            Lote::idPositivo($r['id_matri'] ?? null, 'matrícula'),
            Lote::idPositivo($r['perio'] ?? null, 'periodo'),
            Lote::idPositivo($r['cri'] ?? null, 'criterio'),
            ValorNota::desde($r['nota'] ?? ''),
            TextoLibre::desde($r['conclu'] ?? '', 'conclusión'),
        );
    }

    /** @return array{id_matri: int, perio: int, cri: int, nota: string, conclu: string} formato de SP_REGISTRAR_NOTAS */
    public function paraProcedimiento(): array
    {
        return ['id_matri' => $this->matricula, 'perio' => $this->periodo, 'cri' => $this->criterio,
            'nota' => $this->nota->valor, 'conclu' => $this->conclusiones];
    }
}
