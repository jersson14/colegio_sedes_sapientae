<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * El trabajo de los 4 eventos programados de la BD, ejecutable por cron (Fase 4): el hosting compartido
 * no tiene event_scheduler, y en modo múltiple un solo cron recorre todas las instituciones.
 *
 * Idempotente y compatible con los eventos: si event_scheduler sigue activo, ejecutar las dos cosas no
 * duplica nada (las tareas vencidas ya están cerradas y el cierre de año se registra una sola vez).
 */
final class TareasProgramadas
{
    /** El mismo texto que escriben los eventos de la BD: así cada lado ve si el otro ya lo hizo. */
    public const EVENTO_CIERRE = 'Actualización automática fin de año';

    /** Si el cron estuvo caído en Año Nuevo, el cierre se recupera hasta 7 días después; más tarde, no. */
    private const DIAS_PARA_RECUPERAR = 7;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Eventos actualizar_estado_tareas y actualizar_estado_examen (cada minuto).
     *
     * @return array{tareas: int, examenes: int} filas cerradas
     */
    public function cerrarVencidas(): array
    {
        $tareas = $this->pdo->exec("UPDATE tareas SET estado = 'FINALIZADO' WHERE fecha_entrega <= NOW() AND estado != 'FINALIZADO'");
        $examenes = $this->pdo->exec("UPDATE examen SET estado = 'REALIZADO' WHERE fecha_examen <= NOW() AND estado != 'REALIZADO'");
        return ['tareas' => (int) $tareas, 'examenes' => (int) $examenes];
    }

    /**
     * Eventos actualizar_estado_alumno(s_fin_anio): el 31/12 a las 23:59 todos los alumnos pasan a
     * inactivos y queda constancia en log_eventos_alumnos. Una vez por año, aunque el cron corra cada
     * minuto; fuera de la ventana de recuperación no hace nada (nunca desactiva a mitad de año, p. ej.
     * al instalar el cron en octubre).
     *
     * @return int|null alumnos desactivados, o null si no tocaba
     */
    public function cierreDeAnio(): ?int
    {
        $ahora = new \DateTimeImmutable((string) $this->pdo->query('SELECT NOW()')->fetchColumn());
        $anio = (int) $ahora->format('Y');
        if ($ahora < self::corte($anio)) {
            $anio--;
        }
        if ($ahora > self::corte($anio)->modify('+' . self::DIAS_PARA_RECUPERAR . ' days')) {
            return null;
        }
        // Dos crons simultáneos (o el cron y el evento) no deben cerrar el año dos veces.
        if ((int) $this->pdo->query("SELECT GET_LOCK('cierre_de_anio', 10)")->fetchColumn() !== 1) {
            return null;
        }
        try {
            $hecho = $this->pdo->prepare('SELECT 1 FROM log_eventos_alumnos WHERE evento = ? AND anio = ?');
            $hecho->execute([self::EVENTO_CIERRE, $anio]);
            if ($hecho->fetchColumn() !== false) {
                return null;
            }
            // Transacción propia, o SAVEPOINT si ya hay una abierta (las pruebas de integración).
            $propia = !$this->pdo->inTransaction();
            $propia ? $this->pdo->beginTransaction() : $this->pdo->exec('SAVEPOINT cierre_de_anio');
            try {
                $afectados = (int) $this->pdo->exec("UPDATE alumnos SET alum_estatus = 'NO', updated_at = NOW() WHERE alum_estatus = 'SI'");
                $this->pdo->prepare('INSERT INTO log_eventos_alumnos (evento, anio, fecha_ejecucion, alumnos_afectados) VALUES (?, ?, NOW(), ?)')
                    ->execute([self::EVENTO_CIERRE, $anio, $afectados]);
            } catch (\Throwable $e) {
                $propia ? $this->pdo->rollBack() : $this->pdo->exec('ROLLBACK TO SAVEPOINT cierre_de_anio');
                throw $e;
            }
            $propia ? $this->pdo->commit() : $this->pdo->exec('RELEASE SAVEPOINT cierre_de_anio');
            return $afectados;
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('cierre_de_anio')");
        }
    }

    private static function corte(int $anio): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%d-12-31 23:59:00', $anio));
    }
}
