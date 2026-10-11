<?php

declare(strict_types=1);

namespace App\Asistente;

interface ModeloAsistente
{
    /**
     * @param list<array<string, mixed>> $mensajes
     * @param list<array<string, mixed>> $herramientas definiciones (name, description, inputSchema)
     * @throws FalloModelo si el servicio no respondió
     */
    public function responder(string $sistema, array $mensajes, array $herramientas): RespuestaModelo;
}
