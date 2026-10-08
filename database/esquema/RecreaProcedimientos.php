<?php

declare(strict_types=1);

/**
 * Recrear procedimientos almacenados desde una migración de Phinx sin perder su sql_mode y sin
 * dejar la BD sin el procedimiento si la definición nueva no compila.
 *
 * Extraído de la migración 20261009000000 (que conserva su copia para no reescribir una
 * migración ya aplicada). Úsese en las migraciones nuevas.
 */
trait RecreaProcedimientos
{
    /** Definición vigente en la BD (sin DEFINER), para modificarla sin duplicar el procedimiento entero. */
    private function definicionActual(string $nombre): string
    {
        $fila = $this->fetchRow("SHOW CREATE PROCEDURE `$nombre`");
        $sql = (string) ($fila['Create Procedure'] ?? '');
        return (string) preg_replace('/^CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $sql);
    }

    /**
     * Recrea el procedimiento conservando el sql_mode con que se creó.
     *
     * El DDL de MariaDB no es transaccional: si el CREATE falla tras el DROP, el procedimiento
     * desaparece. Por eso la nueva definición se crea antes con un nombre temporal; solo si
     * compila se reemplaza la real.
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
        $this->execute(self::reemplazarUnaVez($sql, "CREATE PROCEDURE `$nombre`(", "CREATE PROCEDURE `$temporal`("));
        $this->execute("DROP PROCEDURE `$temporal`");
        $this->execute("DROP PROCEDURE IF EXISTS `$nombre`");
        $this->execute($sql);
        $this->execute('SET SESSION sql_mode = @modo_previo');
    }

    /** Reemplaza exactamente una aparición; si hay cero o varias, la migración no adivina. */
    private static function reemplazarUnaVez(string $sql, string $buscar, string $poner): string
    {
        $veces = substr_count($sql, $buscar);
        if ($veces !== 1) {
            throw new RuntimeException("Se esperaba 1 aparición de «{$buscar}» y hay {$veces}");
        }
        return str_replace($buscar, $poner, $sql);
    }
}
