<?php
/**
 * Genera database/migraciones/011_matricula_horarios_salud_auditoria.sql (Fase 8D).
 * Uso: php database/generadores/generar_011.php <carpeta con los SP actuales (SHOW CREATE PROCEDURE)> [base para leer columnas]
 * Los SP existentes se toman de la BD y se modifican con reemplazos verificados (cada texto buscado debe aparecer una vez);
 * los triggers de auditoría se arman con las columnas reales de cada tabla.
 */
[$_, $dir] = $argv;
$bd = $argv[2] ?? 'colegio2';
$salida = __DIR__ . '/../migraciones/011_matricula_horarios_salud_auditoria.sql';
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=$bd;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function sp($dir, $n) {
    $t = str_replace("\r\n", "\n", file_get_contents("$dir/$n.sql"));
    return preg_replace('/^CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $t);
}
function cambiar($t, $buscar, $poner, $nombre) {
    if (substr_count($t, $buscar) !== 1) { fwrite(STDERR, "$nombre: [$buscar] aparece " . substr_count($t, $buscar) . " veces\n"); exit(1); }
    return str_replace($buscar, $poner, $t);
}
function bloque_sp($nombre, $cuerpo) {
    return "\nDROP PROCEDURE IF EXISTS `$nombre`;\nDELIMITER $$\n$cuerpo$$\nDELIMITER ;\n";
}

$sql = file_get_contents(__DIR__ . '/011_parte_fija.sql');

// ---------- Becas: el precio de la pensión de una matrícula descuenta su beca ----------
$t = sp($dir, 'SP_CALCULAR_SALDO_PENSION');
$t = cambiar($t, 'SELECT precio, IFNULL(mora, 0), fecha_vencimiento INTO O_PRECIO, O_MORA, V_VENC',
    'SELECT FN_PRECIO_PENSION(P_MATRI, P_PENSION), IFNULL(mora, 0), fecha_vencimiento INTO O_PRECIO, O_MORA, V_VENC', 'SP_CALCULAR_SALDO_PENSION');
$sql .= "\n-- Saldo de una pensión: el precio ya descuenta la beca de la matrícula\n" . bloque_sp('SP_CALCULAR_SALDO_PENSION', $t);

$t = sp($dir, 'SP_MODIFICAR_PAGO_PENSION');
$t = cambiar($t, "SELECT IF(V_CONCEPTO = 'PENSION', precio, IFNULL(mora, 0)) INTO V_TOPE FROM pensiones WHERE id_pensiones = V_PENSION;",
    "SELECT IF(V_CONCEPTO = 'PENSION', FN_PRECIO_PENSION(V_MATRI, V_PENSION), IFNULL(mora, 0)) INTO V_TOPE FROM pensiones WHERE id_pensiones = V_PENSION;",
    'SP_MODIFICAR_PAGO_PENSION');
$sql .= "\n-- Corregir un pago: el tope de la pensión es el precio con beca\n" . bloque_sp('SP_MODIFICAR_PAGO_PENSION', $t);

// ---------- Vacantes: no matricular ni mover a un aula llena ----------
$t = sp($dir, 'SP_REGISTRAR_MATRICULA');
$t = cambiar($t, "  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;\n",
    "  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;\n"
  . "  -- 6: el aula no tiene vacantes en ese año escolar\n"
  . "  IF FN_VACANTES_AULA(AULA, AÑO, 0) = 0 THEN SELECT 6; LEAVE proc; END IF;\n", 'SP_REGISTRAR_MATRICULA');
$sql .= "\n-- Matrícula: además valida las vacantes del aula (código 6)\n" . bloque_sp('SP_REGISTRAR_MATRICULA', $t);

$t = sp($dir, 'SP_MODIFICAR_MATRICULA');
$t = cambiar($t, "  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;\n",
    "  IF V_CANT > 0 THEN SELECT 2; LEAVE proc; END IF;\n"
  . "  -- 3: el aula nueva no tiene vacantes (la propia matrícula no cuenta)\n"
  . "  IF FN_VACANTES_AULA(AULA, AÑO, ID) = 0 THEN SELECT 3; LEAVE proc; END IF;\n", 'SP_MODIFICAR_MATRICULA');
$sql .= "\n-- Modificar matrícula: el aula nueva debe tener vacantes (código 3)\n" . bloque_sp('SP_MODIFICAR_MATRICULA', $t);

foreach (['SP_LISTAR_AULAS', 'SP_LISTAR_AULAS_FILTRO'] as $n) {
    $t = sp($dir, $n);
    $t = cambiar($t, "\taulas.descripcion, \n", "\taulas.descripcion, \n\taulas.capacidad,\n"
        . "\t(SELECT COUNT(*) FROM matricula m WHERE m.id_aula = aulas.Id_aula\n"
        . "\t    AND m.`id_año` = (SELECT `Id_año_escolar` FROM `año_escolar` WHERE estado = 'ACTIVO' ORDER BY fecha_inicio DESC LIMIT 1)) AS matriculados,\n", $n);
    $sql .= "\n-- Aulas con su capacidad y matriculados del año escolar activo\n" . bloque_sp($n, $t);
}

// ---------- Auditoría: triggers con las columnas reales ----------
$tablas = ['pagos', 'pagos_metodos', 'pago_pensiones', 'ingresos', 'egresos', 'pensiones', 'matricula', 'cuentas_financieras',
           'transferencias', 'cierres_caja', 'metodos_pago', 'planillas', 'planilla_detalle', 'pagos_docentes', 'contratos_docente',
           'conceptos_planilla', 'indicadores', 'empresa', 'usuario'];
// No se guardan: fechas de actualización (cambian siempre) y la contraseña (solo se indica que cambió)
$excluir = ['updated_at', 'updated', 'usu_contra'];
$sql .= "\n-- ---------- Triggers de auditoría ----------\n";
foreach ($tablas as $tabla) {
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = " . $pdo->quote($bd)
        . " AND table_name = " . $pdo->quote($tabla) . " ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    if (!$cols) { fwrite(STDERR, "No existe la tabla $tabla\n"); exit(1); }
    // Columnas que agrega esta misma migración (aún no existen al generarla)
    $nuevas = ['matricula' => ['beca_tipo', 'beca_valor', 'beca_desde', 'beca_motivo']];
    $cols = array_values(array_unique(array_merge($cols, $nuevas[$tabla] ?? [])));
    $pk = $pdo->query("SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = " . $pdo->quote($bd)
        . " AND table_name = " . $pdo->quote($tabla) . " AND constraint_name = 'PRIMARY' LIMIT 1")->fetchColumn();
    $json = function ($fila) use ($cols, $excluir, $tabla) {
        $partes = [];
        foreach ($cols as $c) {
            if (in_array($c, $excluir, true)) { continue; }
            $partes[] = "'" . $c . "', $fila.`$c`";
        }
        if ($tabla === 'usuario') { $partes[] = "'contrasena', MD5($fila.usu_contra)"; }
        return 'JSON_OBJECT(' . implode(', ', $partes) . ')';
    };
    foreach (['INSERT' => 'ins', 'UPDATE' => 'upd', 'DELETE' => 'del'] as $evento => $suf) {
        $nombre = "trg_aud_{$tabla}_{$suf}";
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
echo "ok: " . basename($salida) . "\n";
