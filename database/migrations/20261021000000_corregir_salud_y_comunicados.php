<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo comunicados/enfermería/psicología: defectos que encontró tests/E2E/flujos.mjs §17.
 *
 *  1. SP_MODIFICAR_ATENCION_ENFERMERIA y SP_MODIFICAR_ATENCION_PSICOLOGICA actualizaban por id sin
 *     mirar el tipo: la enfermera podía reescribir una atención PSICOLÓGICA (confidencial) y la
 *     psicóloga una de enfermería. Ahora cada uno solo modifica las de su tipo (0 si no).
 *  2. Registrar tomaba el profesional del «idusu» del formulario y modificar lo reemplazaba; igual con
 *     el autor de los comunicados. Ahora sale de la sesión (controladores) y modificar no lo cambia
 *     (IDUSU / USU se conservan en la firma, ignorados).
 *  3. Respuestas explícitas (1/0) en lugar de nada.
 */
final class CorregirSaludYComunicados extends AbstractMigration
{
    use RecreaProcedimientos;

    private const PROCEDIMIENTOS = [
        'SP_REGISTRAR_ATENCION_ENFERME', 'SP_REGISTRAR_ATENCION_PSICO',
        'SP_MODIFICAR_ATENCION_ENFERMERIA', 'SP_MODIFICAR_ATENCION_PSICOLOGICA',
        'SP_MODIFICAR_COMUNICADO', 'SP_ELIMINAR_COMUNICADO',
    ];

    public function up(): void
    {
        $this->recrear('SP_REGISTRAR_ATENCION_ENFERME', self::registrar('SP_REGISTRAR_ATENCION_ENFERME', 'ENFERMERIA'));
        $this->recrear('SP_REGISTRAR_ATENCION_PSICO', self::registrar('SP_REGISTRAR_ATENCION_PSICO', 'PSICOLOGIA'));
        $this->recrear('SP_MODIFICAR_ATENCION_ENFERMERIA', self::modificar('SP_MODIFICAR_ATENCION_ENFERMERIA', 'ENFERMERIA'));
        $this->recrear('SP_MODIFICAR_ATENCION_PSICOLOGICA', self::modificar('SP_MODIFICAR_ATENCION_PSICOLOGICA', 'PSICOLOGIA'));
        $this->recrear('SP_MODIFICAR_COMUNICADO', self::MODIFICAR_COMUNICADO);
        $this->recrear('SP_ELIMINAR_COMUNICADO', self::ELIMINAR_COMUNICADO);
    }

    public function down(): void
    {
        foreach (self::PROCEDIMIENTOS as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private static function registrar(string $procedimiento, string $tipo): string
    {
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)
BEGIN
    INSERT INTO atencion_salud (id_matricula, id_usuario, tipo_atencion, motivo_consulta, diagnostico, observaciones, created_at, updated_at)
    VALUES (IDESTU, IDUSU, '$tipo', MOTIVO, DIAGNO, OBSERVA, NOW(), NULL);
    SELECT 1;
END
SQL;
    }

    private static function modificar(string $procedimiento, string $tipo): string
    {
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `ID` INT, IN `IDESTU` INT, IN `MOTIVO` VARCHAR(255), IN `DIAGNO` VARCHAR(255), IN `OBSERVA` VARCHAR(255), IN `IDUSU` INT)
BEGIN
    -- Solo las atenciones de este tipo; IDUSU se ignora (quien atendió no cambia al editar).
    IF NOT EXISTS (SELECT 1 FROM atencion_salud WHERE id_atencion = ID AND tipo_atencion = '$tipo') THEN
        SELECT 0;
    ELSE
        UPDATE atencion_salud SET id_matricula = IDESTU, motivo_consulta = MOTIVO, diagnostico = DIAGNO,
            observaciones = OBSERVA, updated_at = NOW()
        WHERE id_atencion = ID AND tipo_atencion = '$tipo';
        SELECT 1;
    END IF;
END
SQL;
    }

    private const MODIFICAR_COMUNICADO = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_COMUNICADO`(IN `ID` INT, IN `TIPO` VARCHAR(255), IN `GRADO` INT, IN `TITU` VARCHAR(255), IN `DESCRI` VARCHAR(255), IN `ESTA` VARCHAR(20), IN `RUTA` VARCHAR(255), IN `USU` INT)
BEGIN
    -- USU se ignora: el autor del comunicado no cambia al editarlo.
    IF NOT EXISTS (SELECT 1 FROM comunicados WHERE id_comunicado = ID) THEN
        SELECT 0;
    ELSE
        UPDATE comunicados SET tipo = TIPO, id_aula = GRADO, titulo = TITU, descripcion = DESCRI,
            imagen = RUTA, estado = ESTA, updated_at = NOW()
        WHERE id_comunicado = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ELIMINAR_COMUNICADO = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_COMUNICADO`(IN `ID` INT)
BEGIN
    DELETE FROM comunicados WHERE id_comunicado = ID;
    SELECT IF(ROW_COUNT() > 0, 1, 0);
END
SQL;
}
