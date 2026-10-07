<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';

/**
 * Fase 3: corrige los defectos restantes que la caracterización dejó documentados (tests/README.md).
 *
 *  1. SP_REGISTRAR_NOTAS: sus variables se llamaban igual que las columnas, así que
 *     «WHERE id_matricula = id_matricula» era siempre verdadero y la validación «matrícula no existe»
 *     nunca se lanzaba; y devolvía el ROW_COUNT del ÚLTIMO insert, no el total. Ahora valida y devuelve
 *     el total insertado (el controlador compara con lo enviado).
 *  2. SP_REGISTRAR_DETALLE_PENSION_PAGO: un concepto fuera del ENUM se guardaba vacío sin error. Ahora
 *     se rechaza con un error explícito.
 *  3. Cuatro listados ordenaban solo por columnas con empates (fecha sin hora, created_at): el orden de
 *     las filas variaba entre peticiones. Se añade un desempate por clave.
 *
 * Los procedimientos se recrean conservando su sql_mode original. down() deja todo como estaba.
 */
final class CorregirNotasConceptosYOrden extends AbstractMigration
{
    /** Procedimiento => [buscar, reemplazar] sobre su definición original. */
    private const DESEMPATES = [
        'SP_LISTAR_ASISTENCIA' => ['ORDER BY fecha DESC', 'ORDER BY fecha DESC, aulas.Id_aula ASC, matricula.`id_año` ASC'],
        'SP_LISTAR_COMPONENTES' => ['ORDER BY criterios.created_at asc',
            'ORDER BY criterios.created_at asc, detalle_asignatura_docente.Id_detalle_asig_docente ASC'],
        'SP_LISTAR_MATRICULADOS' => ['ORDER BY matricula.created_at desc', 'ORDER BY matricula.created_at desc, matricula.id_matricula DESC'],
        'SP_LISTAR_PAGO_PENSION' => ["`año_escolar`.`año_escolar` DESC", "`año_escolar`.`año_escolar` DESC, matricula.id_matricula DESC"],
    ];

    private const VALIDAR_CONCEPTO = <<<'SQL'
    -- Un concepto fuera del ENUM se guardaba vacío sin error (modo SQL permisivo).
    IF CONCEPTO NOT IN ('ADMISION', 'ALUMNO NUEVO', 'MATRICULA', 'PENSION') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Concepto de pago no válido';
    END IF;

SQL;

    public function up(): void
    {
        foreach (self::DESEMPATES as $nombre => [$buscar, $poner]) {
            $this->recrear($nombre, self::reemplazar(esquema_procedimiento_original($nombre), $buscar, $poner));
        }
        $this->recrear('SP_REGISTRAR_NOTAS', self::NOTAS);
        // La validación va después de los DECLARE (MariaDB los exige al inicio del bloque).
        $pago = $this->definicionActual('SP_REGISTRAR_DETALLE_PENSION_PAGO');
        $this->recrear('SP_REGISTRAR_DETALLE_PENSION_PAGO', self::reemplazar(
            $pago,
            "    DECLARE IDPAGO INT;\n",
            "    DECLARE IDPAGO INT;\n\n" . self::VALIDAR_CONCEPTO
        ));
    }

    public function down(): void
    {
        foreach ([...array_keys(self::DESEMPATES), 'SP_REGISTRAR_NOTAS'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
        $pago = $this->definicionActual('SP_REGISTRAR_DETALLE_PENSION_PAGO');
        $this->recrear('SP_REGISTRAR_DETALLE_PENSION_PAGO', self::reemplazar($pago, self::VALIDAR_CONCEPTO, ''));
    }

    /** Definición vigente en la BD (sin DEFINER), para modificarla sin duplicar el procedimiento entero. */
    private function definicionActual(string $nombre): string
    {
        $fila = $this->fetchRow("SHOW CREATE PROCEDURE `$nombre`");
        $sql = (string) ($fila['Create Procedure'] ?? '');
        return (string) preg_replace('/^CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $sql);
    }

    /**
     * Recrea el procedimiento conservando el sql_mode con que se creó (ver migración anterior).
     *
     * El DDL de MariaDB no es transaccional: si el CREATE falla tras el DROP, el procedimiento
     * desaparece (le pasó a SP_REGISTRAR_DETALLE_PENSION_PAGO en una prueba: sin él no se cobra).
     * Por eso la nueva definición se crea antes con un nombre temporal; solo si compila se reemplaza.
     */
    private function recrear(string $nombre, string $sql): void
    {
        $fila = $this->fetchRow("SELECT sql_mode FROM information_schema.routines
            WHERE routine_schema = DATABASE() AND routine_name = '$nombre'");
        $modo = is_array($fila) ? (string) $fila['sql_mode'] : 'NO_AUTO_VALUE_ON_ZERO';
        $temporal = substr($nombre . '_VALIDAR', 0, 64);
        $this->execute('SET @modo_previo = @@SESSION.sql_mode');
        $this->execute('SET SESSION sql_mode = ' . $this->getAdapter()->getConnection()->quote($modo));
        $this->execute("DROP PROCEDURE IF EXISTS `$temporal`");
        $this->execute(self::reemplazar($sql, "CREATE PROCEDURE `$nombre`(", "CREATE PROCEDURE `$temporal`("));
        $this->execute("DROP PROCEDURE `$temporal`");
        $this->execute("DROP PROCEDURE IF EXISTS `$nombre`");
        $this->execute($sql);
        $this->execute('SET SESSION sql_mode = @modo_previo');
    }

    private static function reemplazar(string $sql, string $buscar, string $poner): string
    {
        $veces = substr_count($sql, $buscar);
        if ($veces !== 1) {
            throw new RuntimeException("Se esperaba 1 aparición de «{$buscar}» y hay {$veces}");
        }
        return str_replace($buscar, $poner, $sql);
    }

    private const NOTAS = <<<'SQL'
CREATE PROCEDURE `SP_REGISTRAR_NOTAS`(IN `registros_json` JSON)
BEGIN
    -- Prefijo v_: con el mismo nombre que las columnas, las variables las sombreaban.
    DECLARE idx INT DEFAULT 0;
    DECLARE total INT;
    DECLARE insertadas INT DEFAULT 0;
    DECLARE v_matricula INT;
    DECLARE v_bimestre INT;
    DECLARE v_criterio INT;
    DECLARE v_nota CHAR(5);
    DECLARE v_conclusiones VARCHAR(1000);

    SET total = JSON_LENGTH(registros_json);
    WHILE idx < total DO
        SET v_matricula = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].id_matri')));
        SET v_bimestre = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].perio')));
        SET v_criterio = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].cri')));
        SET v_nota = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].nota')));
        SET v_conclusiones = JSON_UNQUOTE(JSON_EXTRACT(registros_json, CONCAT('$[', idx, '].conclu')));

        IF NOT EXISTS (SELECT 1 FROM matricula WHERE matricula.id_matricula = v_matricula) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: id_matricula no existe en la tabla matricula';
        END IF;

        -- Una nota ya registrada no se sobrescribe ni se duplica.
        INSERT INTO notas (id_matricula, id_bimestre, id_criterio, nota, conclusiones, creared_at)
        SELECT v_matricula, v_bimestre, v_criterio, v_nota, v_conclusiones, NOW()
        FROM dual
        WHERE NOT EXISTS (
            SELECT 1 FROM notas
            WHERE notas.id_matricula = v_matricula AND notas.id_bimestre = v_bimestre AND notas.id_criterio = v_criterio
        );
        SET insertadas = insertadas + ROW_COUNT();
        SET idx = idx + 1;
    END WHILE;

    SELECT insertadas AS inserted_count;
END
SQL;
}
