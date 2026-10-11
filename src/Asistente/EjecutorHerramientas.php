<?php

declare(strict_types=1);

namespace App\Asistente;

interface EjecutorHerramientas
{
    /** @return list<array<string, mixed>> las herramientas que puede usar el rol (name, description, inputSchema) */
    public function definiciones(): array;

    /**
     * @param array<string, mixed> $entrada lo que pidió el modelo (no confiable: se valida)
     * @return array<string, mixed> datos para el modelo, o ['error' => motivo]
     */
    public function ejecutar(string $nombre, array $entrada): array;
}
