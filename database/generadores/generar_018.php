<?php
/**
 * Genera database/migraciones/018_logica_registros_auditoria.sql (Fase 13, paso 1 del plan).
 * Uso: php database/generadores/generar_018.php [base]   (por defecto colegio2)
 *  - SP_REGISTRAR_ALUMNOS y SP_REGISTRAR_ASIGNATURA_DOCENTE: el registro recién creado se toma con LAST_INSERT_ID()
 *    (antes SELECT MAX(id): con dos registros a la vez, los padres o los cursos podían quedar en el registro de otro)
 *    y el estudiante con sus padres se guarda en una transacción.
 *  - Auditoría ampliada: notas, asistencia, calificaciones de tareas, estudiantes, docentes, periodos y año escolar.
 */
$bd = $argv[1] ?? 'colegio2';
$salida = __DIR__ . '/../migraciones/018_logica_registros_auditoria.sql';
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=$bd;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = <<<'SQL'
-- =====================================================================
-- Fase 13 (paso 1 del plan): registros sin cruces y auditoría ampliada
-- Generado por database/generadores/generar_018.php
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Estudiante y sus padres: en una transacción y con el id del registro recién creado
DROP PROCEDURE IF EXISTS `SP_REGISTRAR_ALUMNOS`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_ALUMNOS`(IN P_DNI VARCHAR(20), IN P_NOMBRES VARCHAR(255), IN P_APEPA VARCHAR(255), IN P_APEMA VARCHAR(255),
  IN P_SEXO VARCHAR(20), IN P_FECHANAC DATE, IN P_CEL VARCHAR(20), IN P_DIREC VARCHAR(255), IN P_FOTO VARCHAR(255),
  IN P_DNIPA VARCHAR(20), IN P_DATOSPA VARCHAR(255), IN P_CELPA VARCHAR(255), IN P_DNIMA VARCHAR(20), IN P_DATOSMA VARCHAR(255), IN P_CELMA VARCHAR(255))
BEGIN
  DECLARE V_ID INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF EXISTS (SELECT 1 FROM alumnos WHERE alum_dni = P_DNI) THEN
    SELECT 2;
  ELSE
    START TRANSACTION;
    INSERT INTO alumnos(alum_dni, alum_nombre, alum_apepat, alum_apemat, alum_sexo, alum_fechanacimiento, Edad, alum_movil, alum_direccion,
                        alum_estatus, tipo_alum, alum_fotoperfil, created_at, updated_at)
    VALUES (P_DNI, P_NOMBRES, P_APEPA, P_APEMA, P_SEXO, P_FECHANAC, TIMESTAMPDIFF(YEAR, P_FECHANAC, CURDATE()), P_CEL, P_DIREC,
            'NO', 'NUEVO', P_FOTO, NOW(), NULL);
    SET V_ID = LAST_INSERT_ID();
    INSERT INTO padres(id_alu, Dni_papa, Datos_papa, Celular_papa, Dni_mama, Datos_mama, Celular_mama, created_at, updated_at)
    VALUES (V_ID, P_DNIPA, P_DATOSPA, P_CELPA, P_DNIMA, P_DATOSMA, P_CELMA, NOW(), NULL);
    COMMIT;
    SELECT 1;
  END IF;
END$$
DELIMITER ;

-- Asignación de cursos a un docente: devuelve el id del registro recién creado
DROP PROCEDURE IF EXISTS `SP_REGISTRAR_ASIGNATURA_DOCENTE`;
DELIMITER $$
CREATE PROCEDURE `SP_REGISTRAR_ASIGNATURA_DOCENTE`(IN P_ANIO INT, IN P_IDDOCENTE INT)
BEGIN
  INSERT INTO asignatura_docente(Id_docente, `id_año`, Total_cursos, created_at, updated_at)
  VALUES (P_IDDOCENTE, P_ANIO, 0, NOW(), NULL);
  SELECT LAST_INSERT_ID();
END$$
DELIMITER ;

SQL;

// ---------- Auditoría ampliada (mismo formato que la migración 011) ----------
$tablas = ['notas', 'notas_padre', 'asistencia', 'detalle_tarea', 'alumnos', 'docentes', 'periodos', 'año_escolar'];
$excluir = ['updated_at', 'updated'];
$sql .= "\n-- ---------- Triggers de auditoría ----------\n";
foreach ($tablas as $tabla) {
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = " . $pdo->quote($bd)
        . " AND table_name = " . $pdo->quote($tabla) . " ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    if (!$cols) { fwrite(STDERR, "No existe la tabla $tabla\n"); exit(1); }
    $pk = $pdo->query("SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = " . $pdo->quote($bd)
        . " AND table_name = " . $pdo->quote($tabla) . " AND constraint_name = 'PRIMARY' LIMIT 1")->fetchColumn();
    $json = function ($fila) use ($cols, $excluir) {
        $partes = [];
        foreach ($cols as $c) {
            if (!in_array($c, $excluir, true)) { $partes[] = "'" . $c . "', $fila.`$c`"; }
        }
        return 'JSON_OBJECT(' . implode(', ', $partes) . ')';
    };
    $corto = str_replace('ñ', 'n', $tabla);
    foreach (['INSERT' => 'ins', 'UPDATE' => 'upd', 'DELETE' => 'del'] as $evento => $suf) {
        $nombre = "trg_aud_{$corto}_{$suf}";
        $antes = $evento === 'INSERT' ? 'NULL' : $json('OLD');
        $despues = $evento === 'DELETE' ? 'NULL' : $json('NEW');
        $id = ($evento === 'DELETE' ? 'OLD' : 'NEW') . ".`$pk`";
        $sql .= "\nDROP TRIGGER IF EXISTS `$nombre`;\nDELIMITER $$\nCREATE TRIGGER `$nombre` AFTER $evento ON `$tabla` FOR EACH ROW\nBEGIN\n"
              . "  DECLARE V_ANTES, V_DESPUES LONGTEXT;\n"
              . "  SET V_ANTES = $antes;\n  SET V_DESPUES = $despues;\n"
              . "  IF NOT (V_ANTES <=> V_DESPUES) THEN\n"
              . "    INSERT INTO auditoria(tabla, registro_id, accion, antes, despues, usuario_id, ip, fecha)\n"
              . "    VALUES ('$tabla', $id, '$evento', V_ANTES, V_DESPUES, @app_usuario, @app_ip, NOW());\n"
              . "  END IF;\nEND$$\nDELIMITER ;\n";
    }
}
file_put_contents($salida, $sql);
echo "ok: " . basename($salida) . " (" . count($tablas) * 3 . " triggers)\n";
