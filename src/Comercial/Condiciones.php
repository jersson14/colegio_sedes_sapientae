<?php

declare(strict_types=1);

namespace App\Comercial;

use App\Tenancy\EstadoTenant;

/**
 * Lo que una institución puede hacer según su estado y su plan (Fase 4B.2 y 4B.3):
 *
 *   PRUEBA      → funciona, con aviso y marca de agua en los PDF, y los límites de su plan
 *   ACTIVO      → sin más restricción que los límites de su plan
 *   MOROSO      → aviso; lectura y reportes SÍ, altas y matrícula NO
 *   SUSPENDIDO  → solo el administrador, solo para exportar sus datos, durante la ventana de exportación
 *   CANCELADO   → no se resuelve (404)
 *
 * Nunca se corta de golpe el acceso a los datos académicos por una factura vencida.
 */
final class Condiciones
{
    public function __construct(
        public readonly EstadoTenant $estado,
        public readonly ?Plan $plan = null,
        public readonly ?\DateTimeImmutable $pruebaHasta = null,
        public readonly ?\DateTimeImmutable $exportacionHasta = null,
    ) {
    }

    /** Modo único (instancia propia o dedicada): las condiciones las fija el contrato, no el sistema. */
    public static function sinRestricciones(): self
    {
        return new self(EstadoTenant::Activo);
    }

    public function permiteAltas(): bool
    {
        return in_array($this->estado, [EstadoTenant::Prueba, EstadoTenant::Activo], true);
    }

    public function soloExportacion(): bool
    {
        return $this->estado === EstadoTenant::Suspendido;
    }

    public function esPrueba(): bool
    {
        return $this->estado === EstadoTenant::Prueba;
    }

    public function limite(Recurso $recurso): ?int
    {
        return $this->plan?->limite($recurso);
    }
}
