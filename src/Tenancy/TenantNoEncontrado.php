<?php

declare(strict_types=1);

namespace App\Tenancy;

/** El host no corresponde a ninguna institución que pueda entrar (inexistente, suspendida o cancelada). */
final class TenantNoEncontrado extends \RuntimeException
{
}
