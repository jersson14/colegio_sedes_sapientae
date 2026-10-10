<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Personalización de cada institución (Fase 4.7): color principal y textos de la página pública,
 * en la «empresa» de su propia base (así viajan con sus copias de seguridad).
 *
 * - emp_color: «#rrggbb» o NULL (sin color propio: los colores de siempre).
 * - emp_pagina: JSON con los textos de la página pública (App\Domain\Empresa\PaginaPublica) o NULL
 *   (en modo único, la página de siempre; en modo múltiple, una genérica con los datos de contacto).
 */
final class PersonalizacionEmpresa extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE empresa ADD COLUMN emp_color CHAR(7) NULL DEFAULT NULL AFTER emp_logo,
            ADD COLUMN emp_pagina MEDIUMTEXT NULL DEFAULT NULL AFTER emp_color');
        $this->execute('DROP PROCEDURE IF EXISTS SP_OBTENER_PERSONALIZACION');
        $this->execute('CREATE PROCEDURE SP_OBTENER_PERSONALIZACION()
            SELECT empresa_id, emp_color, emp_pagina FROM empresa ORDER BY empresa_id LIMIT 1');
        $this->execute('DROP PROCEDURE IF EXISTS SP_MODIFICAR_PERSONALIZACION');
        // Devuelve 1 si la institución existe (aunque los valores no cambien), 0 si no hay empresa.
        $this->execute('CREATE PROCEDURE SP_MODIFICAR_PERSONALIZACION(IN COLOR CHAR(7), IN PAGINA MEDIUMTEXT)
            BEGIN
                DECLARE ID INT;
                SET ID = (SELECT MIN(empresa_id) FROM empresa);
                IF ID IS NULL THEN
                    SELECT 0;
                ELSE
                    UPDATE empresa SET emp_color = COLOR, emp_pagina = PAGINA, updated_at = NOW() WHERE empresa_id = ID;
                    SELECT 1;
                END IF;
            END');
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_MODIFICAR_PERSONALIZACION');
        $this->execute('DROP PROCEDURE IF EXISTS SP_OBTENER_PERSONALIZACION');
        $this->execute('ALTER TABLE empresa DROP COLUMN emp_pagina, DROP COLUMN emp_color');
    }
}
