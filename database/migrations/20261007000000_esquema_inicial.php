<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Esquema inicial = esquema actual del sistema (36 tablas, 254 procedimientos, 4 eventos),
 * generado sin datos desde la BD real: database/esquema/esquema_inicial.sql.
 *
 * En una BD que YA tiene el esquema (producción, copias locales) no se ejecuta nada:
 * solo queda registrada como aplicada, y a partir de aquí todo cambio es una migración nueva.
 */
final class EsquemaInicial extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('usuario')) {
            $this->output->writeln('  <comment>Esquema existente detectado: se registra sin ejecutar.</comment>');
            return;
        }
        $sinEventos = false;
        foreach (self::sentencias(__DIR__ . '/../esquema/esquema_inicial.sql') as $sql) {
            // Los 4 eventos programados exigen el privilegio EVENT, que el hosting compartido puede no
            // dar. Su trabajo lo hace igual tools/tareas_programadas.php por cron (Fase 4): sin ese
            // privilegio se omiten y el resto del esquema se crea completo.
            if (preg_match('/\bEVENT\b/', $sql) === 1) {
                try {
                    $this->execute($sql);
                } catch (\PDOException $e) {
                    $sinEventos = true;
                }
                continue;
            }
            $this->execute($sql);
        }
        if ($sinEventos) {
            $this->output->writeln('  <comment>Sin privilegio EVENT: eventos omitidos. Programa tools/tareas_programadas.php en el cron.</comment>');
        }
    }

    public function down(): void
    {
        throw new \Phinx\Migration\IrreversibleMigrationException(
            'El esquema inicial no se revierte: borrar la base es una decisión manual.'
        );
    }

    /**
     * Divide un volcado de mysqldump en sentencias, respetando DELIMITER
     * (los procedimientos y eventos usan ";;" porque su cuerpo contiene ";").
     *
     * @return list<string>
     */
    public static function sentencias(string $archivo): array
    {
        $sentencias = [];
        $actual = '';
        $delimitador = ';';
        foreach (preg_split('/\R/', (string) file_get_contents($archivo)) ?: [] as $linea) {
            if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $linea, $m)) {
                $delimitador = $m[1];
                continue;
            }
            if ($actual === '' && (trim($linea) === '' || str_starts_with(ltrim($linea), '--'))) {
                continue;
            }
            $actual .= $linea . "\n";
            if (str_ends_with(rtrim($linea), $delimitador)) {
                $sql = trim(substr(rtrim($actual), 0, -strlen($delimitador)));
                // Se ejecutan también los /*!40014 SET ... */ de mysqldump: desactivan las
                // claves foráneas mientras se crean las tablas y fijan el sql_mode y el
                // juego de caracteres con que se crea cada procedimiento.
                if ($sql !== '') {
                    $sentencias[] = $sql;
                }
                $actual = '';
            }
        }
        if (trim($actual) !== '') {
            $sentencias[] = trim($actual);
        }
        return $sentencias;
    }
}
