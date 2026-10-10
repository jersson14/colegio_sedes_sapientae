<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PdoEmpresaRepositorio implements EmpresaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function personalizacion(): ?array
    {
        $consulta = $this->pdo->query('CALL SP_OBTENER_PERSONALIZACION()');
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta->closeCursor();
        if (!is_array($fila)) {
            return null;
        }
        return [
            'color' => $fila['emp_color'] !== null ? (string) $fila['emp_color'] : null,
            'pagina' => $fila['emp_pagina'] !== null ? (string) $fila['emp_pagina'] : null,
        ];
    }

    public function guardarPersonalizacion(?string $color, ?string $paginaJson): bool
    {
        $consulta = $this->pdo->prepare('CALL SP_MODIFICAR_PERSONALIZACION(?, ?)');
        $consulta->execute([$color, $paginaJson]);
        $ok = (string) $consulta->fetchColumn() === '1';
        $consulta->closeCursor();
        return $ok;
    }
}
