<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Monto;
use App\Domain\Pension\DatosPension;
use App\Domain\Pension\Pago;
use App\Repositories\PensionRepositorio;
use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/** Pensiones (tarifas por nivel y mes) y su cobro. Quién cobra o anula lo fija el controlador (sesión). */
final class GestionarPensiones
{
    public function __construct(private readonly PensionRepositorio $pensiones)
    {
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function registrar(array $post): int
    {
        return $this->pensiones->registrar(DatosPension::desdeFormulario($post));
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function modificar(array $post): int
    {
        return $this->pensiones->modificar(Lote::idPositivo($post['id'] ?? null, 'pensión'), DatosPension::desdeFormulario($post));
    }

    /** @throws InvalidArgumentException */
    public function eliminar(mixed $id): bool
    {
        return $this->pensiones->eliminar(Lote::idPositivo($id, 'pensión'));
    }

    /**
     * @param array<string, mixed> $post
     * @return bool false si alguno ya estaba pagado (no se cobra ninguno)
     * @throws InvalidArgumentException
     */
    public function cobrar(array $post, int $cobrador): bool
    {
        if ($cobrador <= 0) {
            throw new InvalidArgumentException('Sin usuario que cobre');
        }
        return $this->pensiones->cobrar(Pago::listaDesdeFormulario($post), $cobrador);
    }

    /** @throws InvalidArgumentException */
    public function modificarPago(mixed $id, mixed $monto, mixed $motivo): bool
    {
        $motivo = Texto::deFormulario($motivo);
        Texto::exigirLargo('motivo de la edición', $motivo, 255);
        return $this->pensiones->modificarPago(Lote::idPositivo($id, 'pago'), Monto::desde($monto, 'monto'), $motivo);
    }

    /** @throws InvalidArgumentException */
    public function anularPago(mixed $id, int $anuladoPor): bool
    {
        if ($anuladoPor <= 0) {
            throw new InvalidArgumentException('Sin usuario que anule');
        }
        return $this->pensiones->anularPago(Lote::idPositivo($id, 'pago'), $anuladoPor);
    }
}
