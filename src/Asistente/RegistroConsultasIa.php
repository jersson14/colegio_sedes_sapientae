<?php

declare(strict_types=1);

namespace App\Asistente;

use PDO;

/** Las consultas al asistente en la base de la institución: límites y consumo (que paga la plataforma). */
final class RegistroConsultasIa
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param list<string> $herramientas */
    public function registrar(?int $usuId, string $rol, string $pregunta, array $herramientas, int $entrada, int $salida, string $estado): void
    {
        $consulta = $this->pdo->prepare('CALL SP_REGISTRAR_CONSULTA_IA(?, ?, ?, ?, ?, ?, ?)');
        $consulta->execute([$usuId, $rol, mb_substr($pregunta, 0, 1000), implode(',', array_unique($herramientas)), $entrada, $salida, $estado]);
        $consulta->closeCursor();
    }

    /** Consultas del mes en curso (las que cuentan para el límite mensual). */
    public function delMes(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM asistente_consultas WHERE fecha >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    }

    public function delUsuarioEnLaUltimaHora(int $usuId): int
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM asistente_consultas WHERE usu_id = ? AND fecha >= NOW() - INTERVAL 1 HOUR');
        $consulta->execute([$usuId]);
        return (int) $consulta->fetchColumn();
    }

    /** @return array{mes: string, consultas: int, tokens_entrada: int, tokens_salida: int} */
    public function resumenDelMes(): array
    {
        $fila = (array) $this->pdo->query("SELECT DATE_FORMAT(NOW(), '%Y-%m') AS mes, COUNT(*) AS consultas,
                COALESCE(SUM(tokens_entrada), 0) AS entrada, COALESCE(SUM(tokens_salida), 0) AS salida
              FROM asistente_consultas WHERE fecha >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetch(PDO::FETCH_ASSOC);
        return ['mes' => (string) $fila['mes'], 'consultas' => (int) $fila['consultas'], 'tokens_entrada' => (int) $fila['entrada'],
            'tokens_salida' => (int) $fila['salida']];
    }

    public function purgar(int $dias): int
    {
        $consulta = $this->pdo->prepare('CALL SP_PURGAR_CONSULTAS_IA(?)');
        $consulta->execute([$dias]);
        $borradas = (int) $consulta->fetchColumn();
        $consulta->closeCursor();
        return $borradas;
    }
}
