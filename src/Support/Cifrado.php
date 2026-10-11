<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cifrado simétrico (AES-256-GCM) para secretos que una institución guarda en su base (el token de Nubefact).
 * La clave es APP_CLAVE_CIFRADO en colegio.env (32 bytes en base64), fuera de la BD: una copia de la base o
 * una exportación de datos no basta para leer el secreto.
 *
 * Formato guardado: "v1:" + base64(iv[12] . etiqueta[16] . cifrado).
 */
final class Cifrado
{
    private const PREFIJO = 'v1:';

    private function __construct(private readonly string $clave)
    {
    }

    /** @throws \RuntimeException si la clave no existe o no tiene 32 bytes */
    public static function desdeClave(?string $claveBase64): self
    {
        $clave = base64_decode((string) $claveBase64, true);
        if ($clave === false || strlen($clave) !== 32) {
            throw new \RuntimeException('Falta APP_CLAVE_CIFRADO en colegio.env (32 bytes en base64: php -r "echo base64_encode(random_bytes(32));").');
        }
        return new self($clave);
    }

    public function cifrar(string $texto): string
    {
        $iv = random_bytes(12);
        $etiqueta = '';
        $cifrado = openssl_encrypt($texto, 'aes-256-gcm', $this->clave, OPENSSL_RAW_DATA, $iv, $etiqueta);
        if ($cifrado === false) {
            throw new \RuntimeException('No se pudo cifrar.');
        }
        return self::PREFIJO . base64_encode($iv . $etiqueta . $cifrado);
    }

    /** @throws \RuntimeException si el dato está alterado o se cifró con otra clave */
    public function descifrar(string $guardado): string
    {
        $crudo = str_starts_with($guardado, self::PREFIJO) ? base64_decode(substr($guardado, strlen(self::PREFIJO)), true) : false;
        if ($crudo === false || strlen($crudo) < 28) {
            throw new \RuntimeException('Dato cifrado inválido.');
        }
        $texto = openssl_decrypt(substr($crudo, 28), 'aes-256-gcm', $this->clave, OPENSSL_RAW_DATA, substr($crudo, 0, 12), substr($crudo, 12, 16));
        if ($texto === false) {
            throw new \RuntimeException('No se pudo descifrar: el dato fue alterado o se cifró con otra APP_CLAVE_CIFRADO.');
        }
        return $texto;
    }
}
