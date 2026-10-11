<?php

declare(strict_types=1);

namespace App\Facturacion;

/** Nubefact no respondió (red, tiempo agotado o respuesta ilegible): el comprobante queda PENDIENTE. */
final class FalloConexionNubefact extends \RuntimeException
{
}
