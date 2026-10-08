<?php

declare(strict_types=1);

namespace App\Domain\Nota;

use App\Support\Lote;
use App\Support\TextoLibre;
use InvalidArgumentException;

/**
 * Cambio de una nota ya registrada: del alumno (nota + conclusiones) o de los padres (nota +
 * competencia). Claves del JSON de js/console_notas.js.
 */
final class EdicionNota
{
    private function __construct(
        public readonly int $id,
        public readonly ValorNota $nota,
        public readonly string $texto,
    ) {
    }

    /**
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function delAlumno(array $r): self
    {
        return new self(
            Lote::idPositivo($r['id_nota_bole'] ?? null, 'nota'),
            ValorNota::desde($r['nota'] ?? ''),
            TextoLibre::desde($r['conclusiones'] ?? '', 'conclusión'),
        );
    }

    /**
     * @param array<mixed> $r
     * @throws InvalidArgumentException
     */
    public static function deLosPadres(array $r): self
    {
        $competencia = TextoLibre::desde($r['criterio'] ?? '', 'competencia');
        if ($competencia === '') {
            throw new InvalidArgumentException('Falta la competencia');
        }
        return new self(Lote::idPositivo($r['id_nota_papa'] ?? null, 'nota'), ValorNota::desde($r['nota'] ?? ''), $competencia);
    }
}
