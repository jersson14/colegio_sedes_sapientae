<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Asistente de IA (módulo opcional): registro de cada consulta para los límites (mensual de la institución,
 * por usuario y hora) y para el consumo que la plataforma cobra. La respuesta no se guarda; la pregunta, recortada.
 *
 * - SP_REGISTRAR_CONSULTA_IA: la aplicación solo tiene EXECUTE y SELECT.
 * - SP_PURGAR_CONSULTAS_IA(DIAS): lo llama el cron con la misma retención que la auditoría (mínimo 30 días).
 */
final class AsistenteIa extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE asistente_consultas (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usu_id INT NULL,
            rol VARCHAR(50) NOT NULL DEFAULT '',
            pregunta VARCHAR(1000) NOT NULL DEFAULT '',
            herramientas VARCHAR(500) NOT NULL DEFAULT '',
            tokens_entrada INT NOT NULL DEFAULT 0,
            tokens_salida INT NOT NULL DEFAULT 0,
            estado ENUM('OK','ERROR','RECHAZADA') NOT NULL,
            KEY ix_consultas_fecha (fecha),
            KEY ix_consultas_usuario (usu_id, fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->execute('DROP PROCEDURE IF EXISTS SP_REGISTRAR_CONSULTA_IA');
        $this->execute("CREATE PROCEDURE SP_REGISTRAR_CONSULTA_IA(IN USU INT, IN ROL VARCHAR(50), IN PREGUNTA VARCHAR(1000), IN HERRAMIENTAS VARCHAR(500),
                IN ENTRADA INT, IN SALIDA INT, IN ESTADO VARCHAR(10))
            BEGIN
                INSERT INTO asistente_consultas (usu_id, rol, pregunta, herramientas, tokens_entrada, tokens_salida, estado)
                VALUES (USU, LEFT(IFNULL(ROL, ''), 50), LEFT(IFNULL(PREGUNTA, ''), 1000), LEFT(IFNULL(HERRAMIENTAS, ''), 500),
                        GREATEST(IFNULL(ENTRADA, 0), 0), GREATEST(IFNULL(SALIDA, 0), 0), ESTADO);
                SELECT LAST_INSERT_ID();
            END");
        $this->execute('DROP PROCEDURE IF EXISTS SP_PURGAR_CONSULTAS_IA');
        $this->execute("CREATE PROCEDURE SP_PURGAR_CONSULTAS_IA(IN DIAS INT)
            BEGIN
                IF DIAS < 30 THEN
                    SELECT 0;
                ELSE
                    DELETE FROM asistente_consultas WHERE fecha < NOW() - INTERVAL DIAS DAY;
                    SELECT ROW_COUNT();
                END IF;
            END");
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_PURGAR_CONSULTAS_IA');
        $this->execute('DROP PROCEDURE IF EXISTS SP_REGISTRAR_CONSULTA_IA');
        $this->execute('DROP TABLE IF EXISTS asistente_consultas');
    }
}
