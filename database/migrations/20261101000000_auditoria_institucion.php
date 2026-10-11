<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Auditoría de cada institución (H-15): quién hizo qué, cuándo, desde dónde y con qué resultado.
 *
 * - auditoria_acciones: una fila por petición de escritura (todo lo que no es GET pasa por core/guard.php),
 *   por inicio de sesión (correcto o fallido) y por cierre de sesión. Los datos enviados se guardan sin
 *   contraseñas ni tokens (App\Auditoria\RegistroAuditoria::datosSeguros).
 * - SP_REGISTRAR_AUDITORIA: la aplicación solo tiene EXECUTE y SELECT; escribe por aquí.
 * - SP_PURGAR_AUDITORIA(DIAS): borra lo más antiguo que DIAS (lo llama el cron); devuelve las filas borradas.
 * Es distinta de la tabla auditoria de la BD maestra (lo que hace el superadministrador).
 */
final class AuditoriaInstitucion extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE auditoria_acciones (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usu_id INT NULL,
            usuario VARCHAR(250) NOT NULL DEFAULT '',
            rol VARCHAR(50) NOT NULL DEFAULT '',
            modulo VARCHAR(60) NOT NULL,
            accion VARCHAR(120) NOT NULL,
            estado SMALLINT NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            datos TEXT NULL,
            KEY ix_auditoria_fecha (fecha),
            KEY ix_auditoria_usuario (usu_id, fecha),
            KEY ix_auditoria_modulo (modulo, fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->execute('DROP PROCEDURE IF EXISTS SP_REGISTRAR_AUDITORIA');
        $this->execute("CREATE PROCEDURE SP_REGISTRAR_AUDITORIA(IN USU INT, IN USUARIO VARCHAR(250), IN ROL VARCHAR(50), IN MODULO VARCHAR(60),
                IN ACCION VARCHAR(120), IN ESTADO SMALLINT, IN IP VARCHAR(45), IN DATOS TEXT)
            BEGIN
                INSERT INTO auditoria_acciones (usu_id, usuario, rol, modulo, accion, estado, ip, datos)
                VALUES (USU, LEFT(IFNULL(USUARIO, ''), 250), LEFT(IFNULL(ROL, ''), 50), LEFT(MODULO, 60), LEFT(ACCION, 120), ESTADO,
                        LEFT(IFNULL(IP, ''), 45), DATOS);
                SELECT LAST_INSERT_ID();
            END");
        $this->execute('DROP PROCEDURE IF EXISTS SP_PURGAR_AUDITORIA');
        $this->execute("CREATE PROCEDURE SP_PURGAR_AUDITORIA(IN DIAS INT)
            BEGIN
                IF DIAS < 30 THEN
                    SELECT 0; -- nunca menos de un mes: un valor mal configurado no debe vaciar la auditoría
                ELSE
                    DELETE FROM auditoria_acciones WHERE fecha < NOW() - INTERVAL DIAS DAY;
                    SELECT ROW_COUNT();
                END IF;
            END");
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_PURGAR_AUDITORIA');
        $this->execute('DROP PROCEDURE IF EXISTS SP_REGISTRAR_AUDITORIA');
        $this->execute('DROP TABLE IF EXISTS auditoria_acciones');
    }
}
