<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Anular un ingreso o un egreso sobrescribía id_user (quién cobró o pagó) con quien anulaba, y ese id
 * llegaba del formulario: se perdía el responsable original y se podía atribuir el movimiento a
 * cualquiera. Ahora:
 *
 *  - ingresos y egresos tienen id_usuario_anulacion (quién anuló), y id_user no se toca;
 *  - SP_ANULAR_INGRESOS / SP_ANULAR_EGRESOS solo anulan un movimiento VALIDO y responden 1 si lo
 *    anularon, 0 si no existe o ya estaba anulado (antes no respondían nada y volver a anular
 *    reescribía motivo, fecha y usuario);
 *  - el usuario que anula sale de la sesión (controladores).
 *
 * Los movimientos anulados antes de esta migración tienen en id_user a quien los anuló (el responsable
 * original no es recuperable): se copia a id_usuario_anulacion, que es lo que realmente representa.
 */
final class AnulacionConservaResponsable extends AbstractMigration
{
    use RecreaProcedimientos;

    /** tabla => [clave primaria, procedimiento] */
    private const MOVIMIENTOS = [
        'ingresos' => ['id_ingreso', 'SP_ANULAR_INGRESOS'],
        'egresos' => ['id_egresos', 'SP_ANULAR_EGRESOS'],
    ];

    public function up(): void
    {
        foreach (self::MOVIMIENTOS as $tabla => [$clave, $procedimiento]) {
            $this->execute("ALTER TABLE `$tabla` ADD COLUMN `id_usuario_anulacion` INT(11) NULL DEFAULT NULL AFTER `fecha_anulacion`,
                ADD CONSTRAINT `fk_{$tabla}_anulacion` FOREIGN KEY (`id_usuario_anulacion`) REFERENCES `usuario` (`usu_id`)
                    ON DELETE NO ACTION ON UPDATE CASCADE");
            $this->execute("UPDATE `$tabla` SET id_usuario_anulacion = id_user WHERE estado = 'ANULADO'");
            $this->recrear($procedimiento, self::anular($tabla, $clave, $procedimiento));
        }
    }

    public function down(): void
    {
        foreach (self::MOVIMIENTOS as $tabla => [, $procedimiento]) {
            $this->recrear($procedimiento, esquema_procedimiento_original($procedimiento));
            // Vuelve al comportamiento anterior: en los anulados, id_user era quien anuló.
            $this->execute("UPDATE `$tabla` SET id_user = id_usuario_anulacion WHERE estado = 'ANULADO' AND id_usuario_anulacion IS NOT NULL");
            $this->execute("ALTER TABLE `$tabla` DROP FOREIGN KEY `fk_{$tabla}_anulacion`, DROP COLUMN `id_usuario_anulacion`");
        }
    }

    private static function anular(string $tabla, string $clave, string $procedimiento): string
    {
        return <<<SQL
CREATE PROCEDURE `$procedimiento`(IN `ID` INT, IN `OBSERVA` VARCHAR(255), IN `USU` INT)
BEGIN
    -- id_user (quién cobró o pagó) no se toca; USU es quién anula. Solo se anula lo que está VALIDO.
    UPDATE $tabla SET
        motivo_anulacion = OBSERVA,
        fecha_anulacion = CURDATE(),
        estado = 'ANULADO',
        id_usuario_anulacion = USU
    WHERE $tabla.$clave = ID AND $tabla.estado = 'VALIDO';
    SELECT IF(ROW_COUNT() > 0, 1, 0);
END
SQL;
    }
}
