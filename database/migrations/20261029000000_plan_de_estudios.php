<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Plan de estudios de un instituto (Fase 5.3) y matrícula por unidad didáctica (Fase 5.4):
 *
 *   programa de estudios → módulos formativos → unidades didácticas (créditos, horas, periodo académico)
 *   prerrequisitos entre unidades del mismo programa
 *   matricula_unidades: un alumno, una unidad, un periodo (semestre) — con su estado y nota final
 *
 * Toda escritura va por procedimiento (el usuario de la aplicación no tiene INSERT/UPDATE/DELETE). La regla
 * de los prerrequisitos se comprueba DENTRO de SP_MATRICULAR_UNIDAD: ninguna pantalla puede saltársela.
 */
final class PlanDeEstudios extends AbstractMigration
{
    private const PROCEDIMIENTOS = [
        'SP_GUARDAR_PROGRAMA', 'SP_ELIMINAR_PROGRAMA', 'SP_GUARDAR_MODULO', 'SP_ELIMINAR_MODULO',
        'SP_GUARDAR_UNIDAD', 'SP_ELIMINAR_UNIDAD', 'SP_AGREGAR_PRERREQUISITO', 'SP_QUITAR_PRERREQUISITO',
        'SP_MATRICULAR_UNIDAD', 'SP_RETIRAR_UNIDAD',
    ];

    public function up(): void
    {
        $this->execute("CREATE TABLE programas_estudio (
            id_programa INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(20) NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_programa_codigo (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute("CREATE TABLE modulos_formativos (
            id_modulo INT AUTO_INCREMENT PRIMARY KEY,
            id_programa INT NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_modulo_programa FOREIGN KEY (id_programa) REFERENCES programas_estudio (id_programa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute("CREATE TABLE unidades_didacticas (
            id_unidad INT AUTO_INCREMENT PRIMARY KEY,
            id_modulo INT NOT NULL,
            codigo VARCHAR(20) NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            periodo_academico TINYINT UNSIGNED NOT NULL,
            creditos DECIMAL(4,1) NOT NULL,
            horas_teoricas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            horas_practicas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_unidad_codigo (codigo),
            CONSTRAINT fk_unidad_modulo FOREIGN KEY (id_modulo) REFERENCES modulos_formativos (id_modulo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute("CREATE TABLE prerrequisitos (
            id_unidad INT NOT NULL,
            id_requisito INT NOT NULL,
            PRIMARY KEY (id_unidad, id_requisito),
            CONSTRAINT fk_prerrequisito_unidad FOREIGN KEY (id_unidad) REFERENCES unidades_didacticas (id_unidad) ON DELETE CASCADE,
            CONSTRAINT fk_prerrequisito_requisito FOREIGN KEY (id_requisito) REFERENCES unidades_didacticas (id_unidad) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute("CREATE TABLE matricula_unidades (
            id_matricula_unidad INT AUTO_INCREMENT PRIMARY KEY,
            id_alumno INT NOT NULL,
            id_unidad INT NOT NULL,
            id_periodo INT NOT NULL,
            estado ENUM('MATRICULADO','APROBADO','DESAPROBADO','RETIRADO') NOT NULL DEFAULT 'MATRICULADO',
            nota_final DECIMAL(4,1) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_matricula_unidad (id_alumno, id_unidad, id_periodo),
            KEY ix_matricula_unidad_alumno (id_alumno, estado),
            CONSTRAINT fk_mu_alumno FOREIGN KEY (id_alumno) REFERENCES alumnos (Id_alumno),
            CONSTRAINT fk_mu_unidad FOREIGN KEY (id_unidad) REFERENCES unidades_didacticas (id_unidad),
            CONSTRAINT fk_mu_periodo FOREIGN KEY (id_periodo) REFERENCES periodos (id_periodo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        foreach (self::PROCEDIMIENTOS as $sp) {
            $this->execute("DROP PROCEDURE IF EXISTS $sp");
        }
        // Programas: ID 0 = nuevo. Devuelve el id, o 0 si el código ya es de otro programa.
        $this->execute("CREATE PROCEDURE SP_GUARDAR_PROGRAMA(IN ID INT, IN CODIGO VARCHAR(20), IN NOMBRE VARCHAR(200), IN ESTADO VARCHAR(10))
            BEGIN
                IF EXISTS (SELECT 1 FROM programas_estudio p WHERE p.codigo = CODIGO AND p.id_programa <> ID) THEN
                    SELECT 0;
                ELSEIF ID = 0 THEN
                    INSERT INTO programas_estudio (codigo, nombre, estado) VALUES (CODIGO, NOMBRE, ESTADO);
                    SELECT LAST_INSERT_ID();
                ELSE
                    UPDATE programas_estudio p SET p.codigo = CODIGO, p.nombre = NOMBRE, p.estado = ESTADO WHERE p.id_programa = ID;
                    SELECT ID;
                END IF;
            END");
        // Solo sin módulos (un programa con plan de estudios se desactiva, no se borra).
        $this->execute('CREATE PROCEDURE SP_ELIMINAR_PROGRAMA(IN ID INT)
            BEGIN
                IF EXISTS (SELECT 1 FROM modulos_formativos m WHERE m.id_programa = ID) THEN
                    SELECT 0;
                ELSE
                    DELETE FROM programas_estudio WHERE id_programa = ID;
                    SELECT ROW_COUNT() > 0;
                END IF;
            END');
        $this->execute("CREATE PROCEDURE SP_GUARDAR_MODULO(IN ID INT, IN PROGRAMA INT, IN NOMBRE VARCHAR(200), IN ORDEN TINYINT)
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM programas_estudio p WHERE p.id_programa = PROGRAMA) THEN
                    SELECT 0;
                ELSEIF ID = 0 THEN
                    INSERT INTO modulos_formativos (id_programa, nombre, orden) VALUES (PROGRAMA, NOMBRE, ORDEN);
                    SELECT LAST_INSERT_ID();
                ELSE
                    UPDATE modulos_formativos m SET m.id_programa = PROGRAMA, m.nombre = NOMBRE, m.orden = ORDEN WHERE m.id_modulo = ID;
                    SELECT ID;
                END IF;
            END");
        $this->execute('CREATE PROCEDURE SP_ELIMINAR_MODULO(IN ID INT)
            BEGIN
                IF EXISTS (SELECT 1 FROM unidades_didacticas u WHERE u.id_modulo = ID) THEN
                    SELECT 0;
                ELSE
                    DELETE FROM modulos_formativos WHERE id_modulo = ID;
                    SELECT ROW_COUNT() > 0;
                END IF;
            END');
        // Unidades: devuelve el id, 0 si el módulo no existe o el código ya es de otra unidad.
        $this->execute("CREATE PROCEDURE SP_GUARDAR_UNIDAD(IN ID INT, IN MODULO INT, IN CODIGO VARCHAR(20), IN NOMBRE VARCHAR(200),
                IN PERIODO TINYINT, IN CREDITOS DECIMAL(4,1), IN HT SMALLINT, IN HP SMALLINT, IN ESTADO VARCHAR(10))
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM modulos_formativos m WHERE m.id_modulo = MODULO)
                    OR EXISTS (SELECT 1 FROM unidades_didacticas u WHERE u.codigo = CODIGO AND u.id_unidad <> ID) THEN
                    SELECT 0;
                ELSEIF ID = 0 THEN
                    INSERT INTO unidades_didacticas (id_modulo, codigo, nombre, periodo_academico, creditos, horas_teoricas, horas_practicas, estado)
                        VALUES (MODULO, CODIGO, NOMBRE, PERIODO, CREDITOS, HT, HP, ESTADO);
                    SELECT LAST_INSERT_ID();
                ELSE
                    UPDATE unidades_didacticas u SET u.id_modulo = MODULO, u.codigo = CODIGO, u.nombre = NOMBRE, u.periodo_academico = PERIODO,
                        u.creditos = CREDITOS, u.horas_teoricas = HT, u.horas_practicas = HP, u.estado = ESTADO
                        WHERE u.id_unidad = ID;
                    SELECT ID;
                END IF;
            END");
        // Solo sin matrículas (una unidad con historial se desactiva).
        $this->execute('CREATE PROCEDURE SP_ELIMINAR_UNIDAD(IN ID INT)
            BEGIN
                IF EXISTS (SELECT 1 FROM matricula_unidades mu WHERE mu.id_unidad = ID) THEN
                    SELECT 0;
                ELSE
                    DELETE FROM unidades_didacticas WHERE id_unidad = ID;
                    SELECT ROW_COUNT() > 0;
                END IF;
            END');
        // 1 = agregado; 0 = la misma unidad o de otro programa. Los ciclos (u1 → u2 → u1) los descarta
        // App\Institucion\PlanDeEstudios antes de llamar: MariaDB 10.4 no resuelve las tablas de una CTE
        // recursiva dentro de un procedimiento («No database selected») y aquí no se puede fijar el nombre de la base.
        $this->execute('CREATE PROCEDURE SP_AGREGAR_PRERREQUISITO(IN UNIDAD INT, IN REQUISITO INT)
            BEGIN
                DECLARE programa_unidad INT;
                DECLARE programa_requisito INT;
                SET programa_unidad = (SELECT m.id_programa FROM unidades_didacticas u JOIN modulos_formativos m ON m.id_modulo = u.id_modulo WHERE u.id_unidad = UNIDAD);
                SET programa_requisito = (SELECT m.id_programa FROM unidades_didacticas u JOIN modulos_formativos m ON m.id_modulo = u.id_modulo WHERE u.id_unidad = REQUISITO);
                IF UNIDAD = REQUISITO OR programa_unidad IS NULL OR programa_requisito IS NULL OR programa_unidad <> programa_requisito THEN
                    SELECT 0;
                ELSE
                    INSERT IGNORE INTO prerrequisitos (id_unidad, id_requisito) VALUES (UNIDAD, REQUISITO);
                    SELECT 1;
                END IF;
            END');
        $this->execute('CREATE PROCEDURE SP_QUITAR_PRERREQUISITO(IN UNIDAD INT, IN REQUISITO INT)
            BEGIN
                DELETE FROM prerrequisitos WHERE id_unidad = UNIDAD AND id_requisito = REQUISITO;
                SELECT ROW_COUNT() > 0;
            END');
        // 1 matriculado; 2 ya matriculado en ese periodo; 3 ya aprobó la unidad; 4 le falta un prerrequisito aprobado;
        // 0 alumno, unidad o periodo inexistente, o unidad inactiva. Quien se retiró puede volver en el mismo periodo
        // (se reactiva su fila: la clave alumno-unidad-periodo es única).
        $this->execute("CREATE PROCEDURE SP_MATRICULAR_UNIDAD(IN ALUMNO INT, IN UNIDAD INT, IN PERIODO INT)
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM alumnos a WHERE a.Id_alumno = ALUMNO)
                    OR NOT EXISTS (SELECT 1 FROM unidades_didacticas u WHERE u.id_unidad = UNIDAD AND u.estado = 'ACTIVO')
                    OR NOT EXISTS (SELECT 1 FROM periodos p WHERE p.id_periodo = PERIODO) THEN
                    SELECT 0;
                ELSEIF EXISTS (SELECT 1 FROM matricula_unidades mu WHERE mu.id_alumno = ALUMNO AND mu.id_unidad = UNIDAD AND mu.id_periodo = PERIODO
                        AND mu.estado <> 'RETIRADO') THEN
                    SELECT 2;
                ELSEIF EXISTS (SELECT 1 FROM matricula_unidades mu WHERE mu.id_alumno = ALUMNO AND mu.id_unidad = UNIDAD AND mu.estado = 'APROBADO') THEN
                    SELECT 3;
                ELSEIF EXISTS (
                    SELECT 1 FROM prerrequisitos p
                     WHERE p.id_unidad = UNIDAD
                       AND NOT EXISTS (SELECT 1 FROM matricula_unidades mu WHERE mu.id_alumno = ALUMNO AND mu.id_unidad = p.id_requisito AND mu.estado = 'APROBADO')
                ) THEN
                    SELECT 4;
                ELSE
                    INSERT INTO matricula_unidades (id_alumno, id_unidad, id_periodo) VALUES (ALUMNO, UNIDAD, PERIODO)
                        ON DUPLICATE KEY UPDATE estado = 'MATRICULADO', nota_final = NULL;
                    SELECT 1;
                END IF;
            END");
        // Solo mientras está MATRICULADO (una unidad con nota no se retira: queda en el historial).
        $this->execute("CREATE PROCEDURE SP_RETIRAR_UNIDAD(IN ID INT)
            BEGIN
                UPDATE matricula_unidades SET estado = 'RETIRADO' WHERE id_matricula_unidad = ID AND estado = 'MATRICULADO';
                SELECT ROW_COUNT() > 0;
            END");
    }

    public function down(): void
    {
        foreach (self::PROCEDIMIENTOS as $sp) {
            $this->execute("DROP PROCEDURE IF EXISTS $sp");
        }
        foreach (['matricula_unidades', 'prerrequisitos', 'unidades_didacticas', 'modulos_formativos', 'programas_estudio'] as $tabla) {
            $this->execute("DROP TABLE IF EXISTS $tabla");
        }
    }
}
