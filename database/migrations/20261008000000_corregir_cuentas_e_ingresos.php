<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 3, módulo usuario + pagos: corrige 4 defectos que la caracterización dejó documentados
 * (tests/README.md).
 *
 *  1. SP_REGISTRAR_MATRICULA y SP_REGISTRAR_PERSONAL declaraban `USU` VARCHAR(8): un usuario más
 *     largo se truncaba sin aviso. Ahora VARCHAR(250), igual que la columna usuario.usu_usuario.
 *  2. SP_VERIFICAR_USUARIO comparaba con BINARY (distingue mayúsculas) mientras la matrícula guarda el
 *     usuario en mayúsculas: «mat18e2e» solo entraba como «MAT18E2E». Ahora compara con la collation
 *     de la columna (utf8_spanish_ci). Verificado antes: ningún usuario existente colisiona.
 *  3. Los tres ingresos de la matrícula apuntaban al último pago (MAX) o, en la rama de alumno antiguo,
 *     a una variable de sesión sin recalcular (NULL). Ahora cada ingreso apunta a SU pago (LAST_INSERT_ID).
 *  4. Los ingresos se atribuían siempre al usuario 9. Ahora se registra quién cobra: nuevo parámetro
 *     IDUSUARIO al final de SP_REGISTRAR_MATRICULA y SP_REGISTRAR_DETALLE_PENSION_PAGO.
 *
 * down() restaura las definiciones originales desde database/esquema/esquema_inicial.sql.
 */
final class CorregirCuentasEIngresos extends AbstractMigration
{
    private const AFECTADOS = [
        'SP_VERIFICAR_USUARIO', 'SP_REGISTRAR_PERSONAL', 'SP_REGISTRAR_MATRICULA', 'SP_REGISTRAR_DETALLE_PENSION_PAGO',
    ];

    public function up(): void
    {
        $verificar = self::reemplazar(self::original('SP_VERIFICAR_USUARIO'), 'usuario.usu_usuario = BINARY USU', 'usuario.usu_usuario = USU', 3);
        $personal = self::reemplazar(self::original('SP_REGISTRAR_PERSONAL'), 'IN `USU` VARCHAR(8)', 'IN `USU` VARCHAR(250)', 1);

        foreach ([$verificar, $personal, self::MATRICULA, self::PAGO_PENSION] as $sql) {
            $this->recrear(self::nombre($sql), $sql);
        }
    }

    public function down(): void
    {
        foreach (self::AFECTADOS as $nombre) {
            $this->recrear($nombre, self::original($nombre));
        }
    }

    /**
     * Un procedimiento guarda el sql_mode con el que se creó. Los originales usan uno permisivo
     * (NO_AUTO_VALUE_ON_ZERO; p. ej. insertan '' en columnas de fecha): se conserva al recrearlos,
     * en lugar de heredar el modo estricto por defecto del servidor.
     */
    private function recrear(string $nombre, string $sql): void
    {
        $fila = $this->fetchRow("SELECT sql_mode FROM information_schema.routines
            WHERE routine_schema = DATABASE() AND routine_name = '$nombre'");
        $modo = is_array($fila) ? (string) $fila['sql_mode'] : 'NO_AUTO_VALUE_ON_ZERO';
        $this->execute('SET @modo_previo = @@SESSION.sql_mode');
        $this->execute('SET SESSION sql_mode = ' . $this->getAdapter()->getConnection()->quote($modo));
        $this->execute("DROP PROCEDURE IF EXISTS `$nombre`");
        $this->execute($sql);
        $this->execute('SET SESSION sql_mode = @modo_previo');
    }

    /** Definición original del procedimiento, tal como está en el esquema inicial. */
    private static function original(string $nombre): string
    {
        $sql = (string) file_get_contents(__DIR__ . '/../esquema/esquema_inicial.sql');
        if (!preg_match('/^CREATE PROCEDURE `' . preg_quote($nombre, '/') . '`\(.*?^END ;;$/ms', $sql, $m)) {
            throw new RuntimeException("No se encontró $nombre en el esquema inicial");
        }
        return substr($m[0], 0, -strlen(' ;;'));
    }

    private static function reemplazar(string $sql, string $buscar, string $poner, int $veces): string
    {
        $encontradas = substr_count($sql, $buscar);
        if ($encontradas !== $veces) {
            throw new RuntimeException("Se esperaban $veces apariciones de «{$buscar}» y hay $encontradas");
        }
        return str_replace($buscar, $poner, $sql);
    }

    private static function nombre(string $sql): string
    {
        preg_match('/^CREATE PROCEDURE `([A-Z_]+)`/', $sql, $m);
        return $m[1];
    }

    private const PAGO_PENSION = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_DETALLE_PENSION_PAGO`(IN `ID` INT, IN `CONCEPTO` VARCHAR(30), IN `PENSION` INT, IN `PAGO` DECIMAL(5,2), IN `IDUSUARIO` INT)
BEGIN
    DECLARE VER INT;
    DECLARE IDPAGO INT;

    SELECT COUNT(*) INTO VER FROM pago_pensiones WHERE pago_pensiones.id_matri = ID AND pago_pensiones.id_pension = PENSION;

    IF VER = 0 THEN
        INSERT INTO pago_pensiones(id_matri, concepto, id_pension, fecha_pago, sub_total, created_at, updated_at)
        VALUES (ID, CONCEPTO, PENSION, NOW(), PAGO, NOW(), '');
        SET IDPAGO = LAST_INSERT_ID();

        INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, motivo_anulacion, fecha_anulacion, created_at, updated)
        VALUES (IDPAGO, 1, IDUSUARIO, 1, PAGO, CONCEPTO, 'VALIDO', '', '', CURDATE(), '');
        SELECT 1;
    ELSE
        SELECT 2;
    END IF;
END
SQL;

    private const MATRICULA = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_MATRICULA`(IN `IDESTU` INT, IN `AÑO` INT, IN `AULA` INT, IN `ADMIN` DECIMAL(5,2), IN `NUEVO` DECIMAL(5,2), IN `MATRI` DECIMAL(5,2), IN `PROCEDEN` VARCHAR(100), IN `PROVI` VARCHAR(50), IN `DEPAR` VARCHAR(50), IN `USU` VARCHAR(250), IN `CONTRA` VARCHAR(255), IN `EMAIL` VARCHAR(255), IN `IDUSUARIO` INT)
BEGIN
    DECLARE TIPO VARCHAR(10);
    DECLARE VERI INT;
    DECLARE CANTIDAD INT;
    DECLARE IDUSU INT;
    DECLARE IDMATRI INT;

    SELECT alumnos.tipo_alum INTO TIPO FROM alumnos WHERE alumnos.Id_alumno = IDESTU;

    SELECT DISTINCT usuario.usu_id INTO VERI
    FROM matricula
    INNER JOIN usuario ON matricula.usu_id = usuario.usu_id
    INNER JOIN alumnos ON matricula.id_alumno = alumnos.Id_alumno
    WHERE alumnos.Id_alumno = IDESTU;

    SELECT COUNT(*) INTO CANTIDAD FROM matricula WHERE matricula.id_alumno = IDESTU AND matricula.id_año = AÑO;

    IF (TIPO = 'NUEVO' OR TIPO = 'ANTIGUO') AND CANTIDAD = 0 THEN
        IF TIPO = 'NUEVO' THEN
            INSERT INTO usuario(usu_usuario, usu_contra, usu_email, usu_estatus, rol_id, empresa_id, created_at, updated_at)
            VALUES (USU, CONTRA, EMAIL, 'ACTIVO', '1', '1', NOW(), NOW());
            SET IDUSU = LAST_INSERT_ID();
        ELSE
            UPDATE usuario SET usu_estatus = 'ACTIVO' WHERE usu_id = VERI;
            SET IDUSU = VERI;
        END IF;

        UPDATE alumnos SET alum_estatus = 'SI', tipo_alum = 'ANTIGUO' WHERE Id_alumno = IDESTU;

        INSERT INTO matricula(id_alumno, id_año, id_aula, pago_admi, pago_alu_nuevo, pago_matricula,
                              procedencia_colegio, provincia, departamento, usu_id, created_at, updated_at)
        VALUES (IDESTU, AÑO, AULA, ADMIN, NUEVO, MATRI, PROCEDEN, PROVI, DEPAR, IDUSU, NOW(), NOW());
        SET IDMATRI = LAST_INSERT_ID();

        -- Cada pago con SU ingreso (antes los tres ingresos apuntaban al último pago o a NULL).
        INSERT INTO pago_pensiones(id_matri, concepto, id_pension, fecha_pago, sub_total, created_at, updated_at)
        VALUES (IDMATRI, 'ADMISION', NULL, NOW(), ADMIN, NOW(), NOW());
        INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, motivo_anulacion, fecha_anulacion, created_at, updated)
        VALUES (LAST_INSERT_ID(), 1, IDUSUARIO, 1, ADMIN, 'ADMISION', 'VALIDO', '', '', CURDATE(), '');

        INSERT INTO pago_pensiones(id_matri, concepto, id_pension, fecha_pago, sub_total, created_at, updated_at)
        VALUES (IDMATRI, 'ALUMNO NUEVO', NULL, NOW(), NUEVO, NOW(), NOW());
        INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, motivo_anulacion, fecha_anulacion, created_at, updated)
        VALUES (LAST_INSERT_ID(), 1, IDUSUARIO, 1, NUEVO, 'ALUMNO NUEVO', 'VALIDO', '', '', CURDATE(), '');

        INSERT INTO pago_pensiones(id_matri, concepto, id_pension, fecha_pago, sub_total, created_at, updated_at)
        VALUES (IDMATRI, 'MATRICULA', NULL, NOW(), MATRI, NOW(), NOW());
        INSERT INTO ingresos(id_pago_pension, id_indicador, id_user, cantidad, monto, observacion, estado, motivo_anulacion, fecha_anulacion, created_at, updated)
        VALUES (LAST_INSERT_ID(), 1, IDUSUARIO, 1, MATRI, 'MATRICULA', 'VALIDO', '', '', CURDATE(), '');

        SELECT 1;
    ELSEIF TIPO = 'NUEVO' OR TIPO = 'ANTIGUO' THEN
        SELECT 2;
    END IF;
END
SQL;
}
