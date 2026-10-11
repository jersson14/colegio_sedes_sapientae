<?php

declare(strict_types=1);

namespace App\Asistente;

use Anthropic\Client;

/**
 * Claude por el SDK oficial (anthropic-ai/sdk). Pensamiento adaptativo (el predeterminado del modelo), esfuerzo
 * explícito y, en los modelos que lo admiten, reintento del lado del servidor con otro modelo si el primero declina
 * (fallbacks «default»). El sistema va en caché: es igual en todas las consultas de un mismo rol.
 */
final class ClaudeModeloAsistente implements ModeloAsistente
{
    /** Modelos con fallbacks «default» del servidor. */
    private const CON_FALLBACKS = ['claude-opus-5-5', 'claude-opus-5', 'claude-fable-5-1', 'claude-sonnet-5-5'];

    public function __construct(
        private readonly Client $cliente,
        private readonly string $modelo = 'claude-opus-5-5',
        private readonly string $esfuerzo = 'medium',
    ) {
    }

    public function responder(string $sistema, array $mensajes, array $herramientas): RespuestaModelo
    {
        $fallbacks = in_array($this->modelo, self::CON_FALLBACKS, true);
        try {
            $r = $this->cliente->beta->messages->create(
                maxTokens: 16000,
                messages: $mensajes,
                model: $this->modelo,
                fallbacks: $fallbacks ? 'default' : null,
                outputConfig: ['effort' => $this->esfuerzo],
                system: [['type' => 'text', 'text' => $sistema, 'cacheControl' => ['type' => 'ephemeral']]],
                tools: $herramientas,
                betas: $fallbacks ? ['server-side-fallback-2026-07-01'] : null,
                requestOptions: ['timeout' => 90.0, 'maxRetries' => 2],
            );
        } catch (\Throwable $e) {
            throw new FalloModelo('Servicio de IA: ' . $e::class . ': ' . $e->getMessage(), 0, $e);
        }
        $texto = '';
        $llamadas = [];
        foreach ($r->content as $bloque) {
            if ($bloque->type === 'text' && isset($bloque->text)) {
                $texto .= (string) $bloque->text;
            } elseif ($bloque instanceof \Anthropic\Beta\Messages\BetaToolUseBlock) {
                /** @var array<string, mixed> $entrada */
                $entrada = $bloque->input;
                $llamadas[] = ['id' => $bloque->id, 'nombre' => $bloque->name, 'entrada' => $entrada];
            }
        }
        $uso = $r->usage;
        return new RespuestaModelo(
            (string) ($r->stopReason ?? ''),
            trim($texto),
            $llamadas,
            $r->content,
            $uso->inputTokens + (int) $uso->cacheCreationInputTokens + (int) $uso->cacheReadInputTokens,
            $uso->outputTokens,
        );
    }
}
