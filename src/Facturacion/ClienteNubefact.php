<?php

declare(strict_types=1);

namespace App\Facturacion;

/** La API JSON de Nubefact: un POST a la ruta de la cuenta con el token. */
interface ClienteNubefact
{
    /**
     * @param array<string, mixed> $cuerpo
     * @return array<string, mixed> la respuesta de Nubefact (con «errors» si lo rechazó)
     * @throws FalloConexionNubefact si no hubo respuesta (no se sabe si se emitió: hay que consultar)
     */
    public function enviar(string $ruta, string $token, array $cuerpo): array;
}
