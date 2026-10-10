<?php

declare(strict_types=1);

namespace App\Tenancy;

/** Se pidió una conexión sin haber resuelto antes la institución: es un error de programación. */
final class TenantNoResuelto extends \LogicException
{
}
