<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Roles propios de instituto (Fase 5.9).
 *
 * - Rol SECRETARIA (secretaría académica): plan de estudios, matrícula por unidades, récord y certificados;
 *   sin pagos, usuarios ni configuración. Id 6 (los procedimientos y AltaInstitucion dan por hechos los ids de
 *   los demás roles); si una institución ya usó el 6 para un rol propio, toma el siguiente libre.
 * - unidades_docentes: qué docente dicta cada unidad didáctica en cada periodo. El docente solo califica a los
 *   matriculados en sus unidades de ese periodo (core/pertenencia.php).
 * - SP_ASIGNAR_DOCENTE_UNIDAD(UNIDAD, PERIODO, DOCENTE): 1 asignado (DOCENTE NULL o 0 quita la asignación);
 *   0 si la unidad, el periodo o el docente no existen, o el docente está inactivo.
 */
final class RolesInstituto extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("INSERT INTO roles (Id_rol, tipo_rol, descripcion, estado, created_at)
            SELECT 6, 'SECRETARIA', 'SECRETARÍA ACADÉMICA: PLAN DE ESTUDIOS, MATRÍCULA POR UNIDADES Y CERTIFICADOS', 'ACTIVO', NOW()
              FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM roles WHERE Id_rol = 6 OR tipo_rol = 'SECRETARIA')");
        $this->execute("INSERT INTO roles (tipo_rol, descripcion, estado, created_at)
            SELECT 'SECRETARIA', 'SECRETARÍA ACADÉMICA: PLAN DE ESTUDIOS, MATRÍCULA POR UNIDADES Y CERTIFICADOS', 'ACTIVO', NOW()
              FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tipo_rol = 'SECRETARIA')");

        $this->execute("CREATE TABLE unidades_docentes (
            id_unidad INT NOT NULL,
            id_periodo INT NOT NULL,
            id_docente INT NOT NULL,
            asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_unidad, id_periodo),
            KEY ix_unidades_docentes_docente (id_docente, id_periodo),
            CONSTRAINT fk_ud_unidad FOREIGN KEY (id_unidad) REFERENCES unidades_didacticas (id_unidad) ON DELETE CASCADE,
            CONSTRAINT fk_ud_periodo FOREIGN KEY (id_periodo) REFERENCES periodos (id_periodo),
            CONSTRAINT fk_ud_docente FOREIGN KEY (id_docente) REFERENCES docentes (Id_docente)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->execute('DROP PROCEDURE IF EXISTS SP_ASIGNAR_DOCENTE_UNIDAD');
        $this->execute("CREATE PROCEDURE SP_ASIGNAR_DOCENTE_UNIDAD(IN UNIDAD INT, IN PERIODO INT, IN DOCENTE INT)
            BEGIN
                IF IFNULL(DOCENTE, 0) = 0 THEN
                    DELETE FROM unidades_docentes WHERE id_unidad = UNIDAD AND id_periodo = PERIODO;
                    SELECT 1;
                ELSEIF NOT EXISTS (SELECT 1 FROM unidades_didacticas WHERE id_unidad = UNIDAD)
                    OR NOT EXISTS (SELECT 1 FROM periodos WHERE id_periodo = PERIODO)
                    OR NOT EXISTS (SELECT 1 FROM docentes WHERE Id_docente = DOCENTE AND docente_estatus = 'ACTIVO') THEN
                    SELECT 0;
                ELSE
                    INSERT INTO unidades_docentes (id_unidad, id_periodo, id_docente) VALUES (UNIDAD, PERIODO, DOCENTE)
                        ON DUPLICATE KEY UPDATE id_docente = DOCENTE, asignado_en = NOW();
                    SELECT 1;
                END IF;
            END");
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_ASIGNAR_DOCENTE_UNIDAD');
        $this->execute('DROP TABLE IF EXISTS unidades_docentes');
        // Solo si nadie lo usa: las cuentas con ese rol se quedarían sin rol.
        $this->execute("DELETE FROM roles WHERE tipo_rol = 'SECRETARIA' AND NOT EXISTS (SELECT 1 FROM usuario WHERE usuario.rol_id = roles.Id_rol)");
    }
}
