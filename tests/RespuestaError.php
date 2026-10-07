<?php

declare(strict_types=1);

namespace Tests;

use RuntimeException;

/** Sustituye a la salida HTTP de responder_error() durante las pruebas. */
final class RespuestaError extends RuntimeException
{
}
