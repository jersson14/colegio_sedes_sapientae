<?php

declare(strict_types=1);

namespace App\Facturacion;

/** Cliente HTTP de Nubefact con cURL (sin redirecciones: la ruta ya está validada). */
final class HttpClienteNubefact implements ClienteNubefact
{
    public function __construct(private readonly int $segundos = 30)
    {
    }

    public function enviar(string $ruta, string $token, array $cuerpo): array
    {
        $curl = curl_init($ruta);
        if ($curl === false) {
            throw new FalloConexionNubefact('No se pudo preparar la conexión con Nubefact.');
        }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => (string) json_encode($cuerpo, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Authorization: Token token="' . str_replace(["\r", "\n", '"'], '', $token) . '"', 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->segundos,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        ]);
        $respuesta = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if (!is_string($respuesta) || $respuesta === '') {
            throw new FalloConexionNubefact('Nubefact no respondió' . ($error !== '' ? ": $error" : '.'));
        }
        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            throw new FalloConexionNubefact('Respuesta de Nubefact ilegible.');
        }
        /** @var array<string, mixed> $datos */
        return $datos;
    }
}
