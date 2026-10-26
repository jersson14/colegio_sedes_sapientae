<?php
/**
 * Genera database/migraciones/015_anio_escolar_actual.sql (Fase 11).
 * Uso: php database/generadores/generar_015.php [base]   (por defecto colegio2)
 * Toma de la BD los procedimientos que usan el año del calendario (YEAR(NOW())) como "año escolar actual"
 * y lo cambian por FN_ANIO_ESCOLAR_ACTUAL(), que devuelve el año escolar ACTIVO.
 */
$bd = $argv[1] ?? 'colegio2';
$salida = __DIR__ . '/../migraciones/015_anio_escolar_actual.sql';
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=$bd;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$patron = '/YEAR\s*\(\s*NOW\s*\(\s*\)\s*\)/i';
$nombres = $pdo->query("SELECT routine_name FROM information_schema.routines
                        WHERE routine_schema = '$bd' AND routine_type = 'PROCEDURE' AND routine_definition REGEXP 'YEAR[[:space:]]*\\\\([[:space:]]*NOW'
                        ORDER BY routine_name")->fetchAll(PDO::FETCH_COLUMN);

$sql = <<<'SQL'
-- =====================================================================
-- Fase 11: "año escolar actual" = el año escolar ACTIVO (no el año del calendario)
--   Varios procedimientos buscaban el año escolar cuyo número es YEAR(NOW()). Si el año escolar se llama
--   distinto al año del calendario (p. ej. "2025" con clases en 2026, o un año que empieza en un año y
--   termina en otro), horarios, alumnos por grado, tareas y exámenes pendientes, pagos del estudiante, etc.
--   salían vacíos. Generado por database/generadores/generar_015.php desde los procedimientos actuales.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP FUNCTION IF EXISTS FN_ANIO_ESCOLAR_ACTUAL;
DELIMITER $$
CREATE FUNCTION FN_ANIO_ESCOLAR_ACTUAL() RETURNS INT
    READS SQL DATA
BEGIN
  DECLARE V INT;
  -- 1) el año escolar ACTIVO (si hubiera más de uno, el más reciente)
  SELECT `año_escolar` INTO V FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC, `Id_año_escolar` DESC LIMIT 1;
  -- 2) si ninguno está activo: el que incluye la fecha de hoy
  IF V IS NULL THEN
    SELECT `año_escolar` INTO V FROM `año_escolar` WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin ORDER BY fecha_inicio DESC LIMIT 1;
  END IF;
  RETURN IFNULL(V, YEAR(CURDATE()));
END$$
DELIMITER ;

SQL;

foreach ($nombres as $n) {
    $t = $pdo->query("SHOW CREATE PROCEDURE `$n`")->fetch(PDO::FETCH_ASSOC)['Create Procedure'];
    $t = str_replace("\r\n", "\n", $t);
    $t = preg_replace('/^CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $t);
    $veces = preg_match_all($patron, $t);
    if (!$veces) {
        fwrite(STDERR, "$n: no se encontró YEAR(NOW())\n");
        exit(1);
    }
    $t = preg_replace($patron, 'FN_ANIO_ESCOLAR_ACTUAL()', $t);
    $sql .= "\n-- $n ($veces reemplazo" . ($veces > 1 ? 's' : '') . ")\nDROP PROCEDURE IF EXISTS `$n`;\nDELIMITER $$\n$t$$\nDELIMITER ;\n";
}

// Edad del estudiante: se calculaba una sola vez al registrarlo (columna alumnos.Edad) y nunca cambiaba.
// Los listados la calculan ahora desde la fecha de nacimiento.
$sql .= "\n-- ---------------------------------------------------------------------\n-- Edad calculada siempre a la fecha (antes quedaba fija desde el registro)\n"
      . "-- ---------------------------------------------------------------------\n"
      . "UPDATE alumnos SET Edad = TIMESTAMPDIFF(YEAR, alum_fechanacimiento, CURDATE()) WHERE alum_fechanacimiento IS NOT NULL;\n";
$edad = $pdo->query("SELECT routine_name FROM information_schema.routines
                     WHERE routine_schema = '$bd' AND routine_type = 'PROCEDURE' AND routine_name LIKE 'SP\\_LISTAR\\_%'
                       AND routine_definition LIKE '%alumnos.Edad%' ORDER BY routine_name")->fetchAll(PDO::FETCH_COLUMN);
foreach ($edad as $n) {
    $t = $pdo->query("SHOW CREATE PROCEDURE `$n`")->fetch(PDO::FETCH_ASSOC)['Create Procedure'];
    $t = str_replace("\r\n", "\n", $t);
    $t = preg_replace('/^CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $t);
    if (strpos($t, 'FN_ANIO_ESCOLAR_ACTUAL') === false && preg_match('/YEAR\s*\(\s*NOW/i', $t)) {
        fwrite(STDERR, "$n también usa YEAR(NOW()): revisar\n");
        exit(1);
    }
    $t = preg_replace('/\balumnos\.Edad\b/', 'TIMESTAMPDIFF(YEAR, alumnos.alum_fechanacimiento, CURDATE()) AS Edad', $t, -1, $veces);
    $sql .= "\n-- $n (edad calculada)\nDROP PROCEDURE IF EXISTS `$n`;\nDELIMITER $$\n$t$$\nDELIMITER ;\n";
}

file_put_contents($salida, $sql);
echo count($nombres) . " procedimientos (año escolar) y " . count($edad) . " (edad) -> $salida\n";
