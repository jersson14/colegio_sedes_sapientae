<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Calificación por unidad didáctica (Fase 5.5) y repitencia parcial con recuperación (Fase 5.6).
 *
 * - SP_CALIFICAR_UNIDAD: nota final de una unidad en curso → APROBADO si alcanza la nota mínima, si no
 *   DESAPROBADO (queda como «cargo»: se vuelve a llevar solo esa unidad).
 * - SP_RECUPERAR_UNIDAD: una sola evaluación de recuperación para una unidad desaprobada cuya nota esté en el
 *   rango de recuperación; si alcanza la mínima, la unidad queda APROBADA (con su nota de recuperación).
 * La nota mínima y el rango llegan como parámetros: son la configuración de la institución (App\Institucion\Configuracion).
 */
final class CalificacionPorUnidad extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE matricula_unidades
            ADD COLUMN nota_recuperacion DECIMAL(4,1) NULL AFTER nota_final,
            ADD COLUMN calificado_en DATETIME NULL AFTER nota_recuperacion');
        $this->execute('DROP PROCEDURE IF EXISTS SP_CALIFICAR_UNIDAD');
        // 1 calificado; 0 no existe o no está en curso (una unidad calificada no se recalifica aquí); 2 nota fuera de 0–20.
        $this->execute("CREATE PROCEDURE SP_CALIFICAR_UNIDAD(IN ID INT, IN NOTA DECIMAL(4,1), IN MINIMA DECIMAL(4,1))
            BEGIN
                IF NOTA < 0 OR NOTA > 20 THEN
                    SELECT 2;
                ELSE
                    UPDATE matricula_unidades
                       SET nota_final = NOTA, calificado_en = NOW(), estado = IF(NOTA >= MINIMA, 'APROBADO', 'DESAPROBADO')
                     WHERE id_matricula_unidad = ID AND estado = 'MATRICULADO';
                    SELECT ROW_COUNT() > 0;
                END IF;
            END");
        $this->execute('DROP PROCEDURE IF EXISTS SP_RECUPERAR_UNIDAD');
        // 1 aprobada en recuperación; 3 rendida pero sigue desaprobada; 0 no procede (no está desaprobada, ya tuvo
        // recuperación o su nota está fuera del rango); 2 nota fuera de 0–20.
        $this->execute("CREATE PROCEDURE SP_RECUPERAR_UNIDAD(IN ID INT, IN NOTA DECIMAL(4,1), IN MINIMA DECIMAL(4,1), IN DESDE DECIMAL(4,1))
            BEGIN
                IF NOTA < 0 OR NOTA > 20 THEN
                    SELECT 2;
                ELSEIF NOT EXISTS (SELECT 1 FROM matricula_unidades mu WHERE mu.id_matricula_unidad = ID AND mu.estado = 'DESAPROBADO'
                        AND mu.nota_recuperacion IS NULL AND mu.nota_final >= DESDE AND mu.nota_final < MINIMA) THEN
                    SELECT 0;
                ELSE
                    UPDATE matricula_unidades
                       SET nota_recuperacion = NOTA, calificado_en = NOW(), estado = IF(NOTA >= MINIMA, 'APROBADO', 'DESAPROBADO')
                     WHERE id_matricula_unidad = ID;
                    SELECT IF(NOTA >= MINIMA, 1, 3);
                END IF;
            END");
    }

    public function down(): void
    {
        $this->execute('DROP PROCEDURE IF EXISTS SP_RECUPERAR_UNIDAD');
        $this->execute('DROP PROCEDURE IF EXISTS SP_CALIFICAR_UNIDAD');
        $this->execute('ALTER TABLE matricula_unidades DROP COLUMN calificado_en, DROP COLUMN nota_recuperacion');
    }
}
