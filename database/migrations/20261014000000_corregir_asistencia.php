<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo asistencia: defectos que encontró tests/E2E/flujos.mjs §12.
 *
 *  1. SP_REGISTRAR_ASISTENCIA buscaba el duplicado por la fecha de REGISTRO (DATE(created_at)) en
 *     lugar de la fecha de la asistencia: registrar dos veces un día pasado lo duplicaba. Y guardaba
 *     mes = MONTH(NOW()) en lugar del mes de la asistencia (los reportes mensuales usan esa columna).
 *  2. SP_ACTUALIZAR_ASISTENCIA cambiaba la fecha (el panel la muestra deshabilitada; una petición
 *     manipulada podía mover el registro sobre otro día y duplicarlo) y respondía éxito aunque el
 *     registro no existiera. Ahora no toca la fecha y responde 1 si existe, 2 si no (lo que espera el
 *     panel). El parámetro FECHA se conserva en la firma pero se ignora.
 *
 * El estado (ENUM) y la observación (escapada, hasta 1000) los valida App\Domain\Asistencia.
 */
final class CorregirAsistencia extends AbstractMigration
{
    use RecreaProcedimientos;

    public function up(): void
    {
        $this->recrear('SP_REGISTRAR_ASISTENCIA', self::REGISTRAR);
        $this->recrear('SP_ACTUALIZAR_ASISTENCIA', self::ACTUALIZAR);
    }

    public function down(): void
    {
        foreach (['SP_REGISTRAR_ASISTENCIA', 'SP_ACTUALIZAR_ASISTENCIA'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private const REGISTRAR = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_ASISTENCIA`(IN `ID_MATRI` INT, IN `FECHA` DATE, IN `ESTA` VARCHAR(20), IN `OBSER` VARCHAR(1000))
BEGIN
    IF EXISTS (SELECT 1 FROM asistencia WHERE asistencia.id_matricula = ID_MATRI AND asistencia.fecha = FECHA) THEN
        SELECT 2;
    ELSE
        INSERT INTO asistencia(id_matricula, mes, fecha, estado, observacion, created_at, updated_at)
        VALUES (ID_MATRI, MONTH(FECHA), FECHA, ESTA, OBSER, NOW(), NULL);
        SELECT 1;
    END IF;
END
SQL;

    private const ACTUALIZAR = <<<'SQL'
CREATE PROCEDURE `SP_ACTUALIZAR_ASISTENCIA`(IN `ID_ASIS` INT, IN `FECHA` DATE, IN `ESTA` VARCHAR(20), IN `OBSER` VARCHAR(1000))
BEGIN
    -- FECHA se ignora: editar cambia el estado y la observación de ESE día.
    IF EXISTS (SELECT 1 FROM asistencia WHERE asistencia.id_asistencia = ID_ASIS) THEN
        UPDATE asistencia SET estado = ESTA, observacion = OBSER, updated_at = NOW()
        WHERE id_asistencia = ID_ASIS;
        SELECT 1;
    ELSE
        SELECT 2;
    END IF;
END
SQL;
}
