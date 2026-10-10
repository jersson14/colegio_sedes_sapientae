<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configuración de la institución (Fase 5.1): tipo (COLEGIO/INSTITUTO/CETPRO) y reglas académicas que
 * cambian entre colegios e institutos (periodo, nota mínima, ponderación, apoderado, modo de matrícula).
 *
 * En la base de cada institución (viaja con sus respaldos y sirve también en modo único). Solo se guarda lo
 * que difiere de los valores por defecto de su tipo (App\Institucion\Configuracion). La aplicación escribe
 * por procedimiento: su usuario de BD no tiene INSERT/UPDATE directos.
 */
final class ConfiguracionInstitucion extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE configuracion (
            clave VARCHAR(80) NOT NULL,
            valor VARCHAR(500) NOT NULL,
            actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (clave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $this->execute('DROP PROCEDURE IF EXISTS SP_GUARDAR_CONFIGURACION');
        // VALOR NULL = volver al valor por defecto del tipo de institución.
        $this->execute('CREATE PROCEDURE SP_GUARDAR_CONFIGURACION(IN CLAVE VARCHAR(80), IN VALOR VARCHAR(500))
            BEGIN
                IF VALOR IS NULL THEN
                    DELETE FROM configuracion WHERE configuracion.clave = CLAVE;
                ELSE
                    INSERT INTO configuracion (clave, valor) VALUES (CLAVE, VALOR)
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor);
                END IF;
                SELECT 1;
            END');
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_GUARDAR_CONFIGURACION');
        $this->execute('DROP TABLE IF EXISTS configuracion');
    }
}
