<?php

declare(strict_types=1);

namespace App\Asistente;

/** El servicio de IA no respondió o rechazó la petición (el detalle va al log, no al usuario). */
final class FalloModelo extends \RuntimeException
{
}
