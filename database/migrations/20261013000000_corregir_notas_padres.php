<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo notas: SP_REGISTRAR_NOTAS_PADRES usaba ON DUPLICATE KEY UPDATE, pero notas_padre no
 * tiene ninguna clave única: cada vez que se guardaban las notas de los padres se insertaban filas
 * nuevas y el boletín las mostraba repetidas (lo encontró tests/E2E/flujos.mjs §11).
 *
 * No se añade la clave única porque fallaría en una BD que ya tenga duplicados; el SP busca la fila
 * (matrícula, periodo, criterio) y la actualiza, o la inserta si no existe. Devuelve cuántos registros
 * procesó (uno por registro; antes, 2 por cada actualización). Variables con prefijo v_ (antes se
 * llamaban igual que las columnas).
 *
 * Los largos (nota CHAR(5), criterio y conclusiones VARCHAR(255), que los SP declaraban más largos y la
 * columna truncaba) y la escala de la nota los valida App\Domain\Nota.
 */
final class CorregirNotasPadres extends AbstractMigration
{
    use RecreaProcedimientos;

    public function up(): void
    {
        $this->recrear('SP_REGISTRAR_NOTAS_PADRES', self::REGISTRAR);
    }

    public function down(): void
    {
        $this->recrear('SP_REGISTRAR_NOTAS_PADRES', esquema_procedimiento_original('SP_REGISTRAR_NOTAS_PADRES'));
    }

    private const REGISTRAR = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_NOTAS_PADRES`(IN `registros_json` JSON)
BEGIN
    DECLARE idx INT DEFAULT 0;
    DECLARE total INT;
    DECLARE procesados INT DEFAULT 0;
    DECLARE v_matricula INT;
    DECLARE v_bimestre INT;
    DECLARE v_criterio VARCHAR(255);
    DECLARE v_nota CHAR(5);
    DECLARE v_existente INT;

    SET total = JSON_LENGTH(registros_json);
    WHILE idx < total DO
        SET v_matricula = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].id_matri')));
        SET v_bimestre = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].perio')));
        SET v_criterio = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].competencia')));
        SET v_nota = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].nota')));

        SET v_existente = NULL;
        SELECT MIN(notas_padre.id_nota_papa) INTO v_existente FROM notas_padre
        WHERE notas_padre.id_matricula = v_matricula AND notas_padre.id_bimestre = v_bimestre
          AND notas_padre.criterio = v_criterio;

        IF v_existente IS NULL THEN
            INSERT INTO notas_padre (id_matricula, id_bimestre, criterio, nota, creared_at, updated_at)
            VALUES (v_matricula, v_bimestre, v_criterio, v_nota, NOW(), NOW());
        ELSE
            UPDATE notas_padre SET nota = v_nota, updated_at = NOW() WHERE id_nota_papa = v_existente;
        END IF;
        SET procesados = procesados + 1;
        SET idx = idx + 1;
    END WHILE;

    SELECT procesados AS processed_count;
END
SQL;
}
