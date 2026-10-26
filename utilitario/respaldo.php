<?php
/**
 * Respaldos de la base de datos desde el sistema (Fase 11), sin depender de mysqldump (muchos hostings no lo tienen).
 * Genera un .sql.gz con tablas, datos, vistas, funciones, procedimientos y triggers, que se restaura con:
 *   gunzip < archivo.sql.gz | mysql --default-character-set=utf8mb4 -u USUARIO -p BASE
 * Los respaldos quedan en respaldos/ (protegida por .htaccess) y se descargan por el controlador.
 */
require_once __DIR__ . '/regional.php';

const RESPALDOS_DIR = __DIR__ . '/../respaldos';
const RESPALDO_PATRON = '/^[\w.-]+\.sql(\.gz)?$/';

/** Respaldos existentes, del más nuevo al más antiguo. */
function respaldos_listar() {
    $lista = [];
    foreach (glob(RESPALDOS_DIR . '/*.{sql,sql.gz}', GLOB_BRACE) ?: [] as $f) {
        $n = basename($f);
        $lista[] = [
            'archivo' => $n,
            'tamano' => filesize($f),
            'fecha' => date('Y-m-d H:i:s', filemtime($f)),
            'tipo' => strpos($n, '_antes_') !== false ? 'Antes de una actualización'
                : (strpos($n, '_manual') !== false ? 'Generado desde el sistema' : 'Automático (diario)'),
        ];
    }
    usort($lista, function ($a, $b) { return strcmp($b['fecha'], $a['fecha']); });
    return $lista;
}

/** Ruta segura de un respaldo existente o null (solo nombres simples dentro de respaldos/). */
function respaldo_ruta($archivo) {
    $n = basename((string) $archivo);
    if (!preg_match(RESPALDO_PATRON, $n)) {
        return null;
    }
    $ruta = RESPALDOS_DIR . '/' . $n;
    return is_file($ruta) ? $ruta : null;
}

/** Quita "DEFINER=`usuario`@`host`" para que el respaldo se pueda restaurar con otro usuario de base de datos. */
function respaldo_sin_definer($sql) {
    return preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $sql);
}

/**
 * Genera el respaldo y devuelve ['archivo', 'tamano', 'tablas', 'filas', 'rutinas', 'triggers', 'vistas', 'avisos'].
 * Usa una conexión propia en utf8mb4 y una lectura consistente (todas las tablas en el mismo instante).
 */
function respaldo_generar() {
    $config = require __DIR__ . '/../config/config.php';
    // Usuario propio para respaldos (opcional, ver scripts/usuario_respaldo.sql): el de la aplicación no suele
    // tener permiso para leer vistas, procedimientos y triggers
    $db = array_replace($config['db'], array_filter($config['db_respaldo'] ?? []));
    date_default_timezone_set(config_regional()['zona_horaria']);
    set_time_limit(0);

    if (!is_dir(RESPALDOS_DIR)) {
        mkdir(RESPALDOS_DIR, 0750, true);
    }
    // un solo respaldo a la vez
    $candado = fopen(RESPALDOS_DIR . '/.generando.lock', 'c');
    if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Ya se está generando un respaldo; espere unos segundos.');
    }

    $pdo = new PDO("mysql:host={$db['host']};port={$db['puerto']};dbname={$db['nombre']};charset=utf8mb4", $db['usuario'], $db['contrasena'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
    ]);
    $pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
    $pdo->exec("START TRANSACTION WITH CONSISTENT SNAPSHOT");
    $consulta = function ($sql, $p = []) use ($pdo) {
        $q = $pdo->prepare($sql);
        $q->execute($p);
        $r = $q->fetchAll(PDO::FETCH_ASSOC);
        $q->closeCursor();
        return $r;
    };

    $nombre = $db['nombre'] . '_' . date('Ymd_His') . '_manual.sql.gz';
    $ruta = RESPALDOS_DIR . '/' . $nombre;
    $temporal = $ruta . '.parcial';
    $gz = gzopen($temporal, 'wb6');
    $w = function ($s) use ($gz) { gzwrite($gz, $s); };
    $res = ['archivo' => $nombre, 'tablas' => 0, 'filas' => 0, 'rutinas' => 0, 'triggers' => 0, 'vistas' => 0, 'avisos' => [], 'sin_permiso' => 0];

    try {
        $w("-- Respaldo de la base `{$db['nombre']}` generado desde el sistema el " . date('Y-m-d H:i:s') . "\n"
            . "-- Restaurar: gunzip < archivo.sql.gz | mysql --default-character-set=utf8mb4 -u USUARIO -p BASE\n\n"
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n");
        $pdo->exec("SET time_zone = '+00:00'");

        $objetos = $consulta("SELECT table_name AS n, table_type AS t FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name", [$db['nombre']]);
        $columnas = [];
        foreach ($consulta("SELECT table_name AS t, column_name AS c, data_type AS d, extra AS e FROM information_schema.columns
                             WHERE table_schema = ? ORDER BY table_name, ordinal_position", [$db['nombre']]) as $c) {
            if (stripos($c['e'], 'GENERATED') === false) {   // las columnas calculadas no se insertan
                $columnas[$c['t']][] = $c;
            }
        }

        // Permisos del usuario de la base para leer los triggers (sin ellos information_schema no los muestra)
        $permisos = strtoupper(implode(' ', array_map('current', $consulta('SHOW GRANTS'))));
        $lee_triggers = strpos($permisos, 'ALL PRIVILEGES') !== false || strpos($permisos, 'TRIGGER') !== false;

        // 1) Tablas y datos
        foreach ($objetos as $o) {
            if ($o['t'] !== 'BASE TABLE') { continue; }
            $t = $o['n'];
            $crear = $consulta("SHOW CREATE TABLE `$t`")[0]['Create Table'];
            $w("--\n-- Tabla `$t`\n--\nDROP TABLE IF EXISTS `$t`;\n$crear;\n\n");
            $cols = $columnas[$t] ?? [];
            $lista = implode(', ', array_map(function ($c) { return '`' . $c['c'] . '`'; }, $cols));
            $binarias = [];
            foreach ($cols as $i => $c) {
                if (preg_match('/blob|binary|bit/i', $c['d'])) { $binarias[$i] = true; }
            }
            $q = $pdo->query("SELECT $lista FROM `$t`");
            $lote = [];
            while ($fila = $q->fetch(PDO::FETCH_NUM)) {
                foreach ($fila as $i => &$v) {
                    $v = $v === null ? 'NULL' : (isset($binarias[$i]) ? '0x' . bin2hex($v) : $pdo->quote($v));
                }
                unset($v);
                $lote[] = '(' . implode(',', $fila) . ')';
                $res['filas']++;
                if (count($lote) >= 200) {
                    $w("INSERT INTO `$t` ($lista) VALUES\n" . implode(",\n", $lote) . ";\n");
                    $lote = [];
                }
            }
            $q->closeCursor();
            if ($lote) {
                $w("INSERT INTO `$t` ($lista) VALUES\n" . implode(",\n", $lote) . ";\n");
            }
            $w("\n");
            $res['tablas']++;
        }

        // 2) Funciones (las vistas pueden usarlas, p. ej. FN_PRECIO_PENSION) y, después de las vistas, 3) procedimientos
        $rutinas = function ($tipo) use ($consulta, $db, $w, &$res) {
            foreach ($consulta("SELECT routine_name AS n FROM information_schema.routines WHERE routine_schema = ? AND routine_type = ? ORDER BY routine_name",
                               [$db['nombre'], $tipo]) as $r) {
                $fila = $consulta("SHOW CREATE $tipo `{$r['n']}`")[0] ?? [];
                $cuerpo = $fila[$tipo === 'FUNCTION' ? 'Create Function' : 'Create Procedure'] ?? null;
                if (!$cuerpo) {
                    $res['sin_permiso']++;
                    continue;
                }
                $w("DROP $tipo IF EXISTS `{$r['n']}`;;\n" . respaldo_sin_definer($cuerpo) . ";;\n\n");
                $res['rutinas']++;
            }
        };
        $w("DELIMITER ;;\n");
        $rutinas('FUNCTION');
        $w("DELIMITER ;\n\n");

        // Vistas: una vista que usa otra va después de ella
        $vistas = [];
        foreach ($objetos as $o) {
            if ($o['t'] === 'VIEW') {
                try {
                    $crear = $consulta("SHOW CREATE VIEW `{$o['n']}`")[0]['Create View'];
                } catch (PDOException $e) {
                    $res['sin_permiso']++;
                    continue;
                }
                $vistas[$o['n']] = preg_replace('/\sSQL SECURITY DEFINER/', '', respaldo_sin_definer($crear));
            }
        }
        while (count($vistas)) {
            $siguiente = null;
            foreach ($vistas as $v => $crear) {
                $usa_pendiente = false;
                foreach ($vistas as $otra => $x) {
                    if ($otra !== $v && strpos($crear, '`' . $otra . '`') !== false) { $usa_pendiente = true; }
                }
                if (!$usa_pendiente) { $siguiente = $v; break; }
            }
            $siguiente = $siguiente ?? array_key_first($vistas);   // referencia circular (no debería pasar)
            $w("DROP VIEW IF EXISTS `$siguiente`;\n{$vistas[$siguiente]};\n\n");
            unset($vistas[$siguiente]);
            $res['vistas']++;
        }

        $w("DELIMITER ;;\n");
        $rutinas('PROCEDURE');

        // 4) Triggers al final: si se crearan antes, al restaurar los datos se llenaría la auditoría de nuevo
        if (!$lee_triggers) {
            $res['sin_permiso']++;
        }
        foreach ($consulta("SELECT trigger_name AS n FROM information_schema.triggers WHERE trigger_schema = ? ORDER BY event_object_table, action_order", [$db['nombre']]) as $tr) {
            $fila = $consulta("SHOW CREATE TRIGGER `{$tr['n']}`")[0] ?? [];
            $cuerpo = $fila['SQL Original Statement'] ?? null;
            if (!$cuerpo) {
                $res['sin_permiso']++;
                continue;
            }
            $w("DROP TRIGGER IF EXISTS `{$tr['n']}`;;\n" . respaldo_sin_definer($cuerpo) . ";;\n\n");
            $res['triggers']++;
        }
        $w("DELIMITER ;\n\nSET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n-- Fin del respaldo\n");
        $pdo->exec("COMMIT");
        gzclose($gz);
        rename($temporal, $ruta);
    } catch (Throwable $e) {
        gzclose($gz);
        @unlink($temporal);
        throw $e;
    } finally {
        flock($candado, LOCK_UN);
        fclose($candado);
    }

    if ($res['sin_permiso']) {
        $res['avisos'][] = 'El respaldo tiene todas las tablas y datos, pero el usuario de la base de datos no tiene permiso para leer '
            . 'vistas, procedimientos o triggers (' . $res['sin_permiso'] . ' objetos), así que este respaldo NO basta para reconstruir el sistema. '
            . "Configure el usuario de respaldo 'db_respaldo' (scripts/usuario_respaldo.sql) y conserve los respaldos automáticos diarios, que son completos.";
    }
    unset($res['sin_permiso']);
    $res['tamano'] = filesize($ruta);
    @file_put_contents(RESPALDOS_DIR . '/respaldos.log', date('Y-m-d H:i:s') . " respaldo $nombre (sistema)\n", FILE_APPEND);
    return $res;
}
