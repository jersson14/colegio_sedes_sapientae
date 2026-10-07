<?php

declare(strict_types=1);

/**
 * Utilidades para leer database/esquema/esquema_inicial.sql desde las migraciones.
 */

/**
 * Divide un volcado de mysqldump en sentencias, respetando DELIMITER
 * (los procedimientos y eventos usan ";;" porque su cuerpo contiene ";").
 *
 * @return list<string>
 */
function esquema_sentencias(string $archivo): array
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

/** Definición original (CREATE PROCEDURE …) de un procedimiento del esquema inicial. */
function esquema_procedimiento_original(string $nombre): string
{
    foreach (esquema_sentencias(__DIR__ . '/esquema_inicial.sql') as $sql) {
        if (str_starts_with($sql, 'CREATE PROCEDURE `' . $nombre . '`(')) {
            return $sql;
        }
    }
    throw new RuntimeException("No se encontró $nombre en el esquema inicial");
}
