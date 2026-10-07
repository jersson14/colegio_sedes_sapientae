<?php

/**
 * Repara los ingresos HISTÓRICOS mal enlazados por el defecto de SP_REGISTRAR_MATRICULA
 * (corregido en la migración 20261008000000_corregir_cuentas_e_ingresos).
 *
 * Por defecto solo INFORMA. Con --aplicar repara, en una transacción, únicamente lo que no admite duda:
 *   A. Ingreso de ADMISION/ALUMNO NUEVO/MATRICULA enlazado al pago de OTRO concepto → se enlaza al pago
 *      de su mismo concepto en la misma matrícula, si esa matrícula tiene exactamente uno.
 *   B. Ingreso sin pago (NULL) → se enlaza al pago del mismo concepto, monto y día de registro que no
 *      tenga ya ingreso, si hay exactamente un candidato.
 * Lo ambiguo se lista y se deja como está. El usuario que cobró (id_user = 9) no es recuperable.
 *
 * Uso: php tools/reparar_ingresos.php [--aplicar]      (lee la BD de colegio.env)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../model/model_conexion.php';

$aplicar = in_array('--aplicar', $argv, true);
$pdo = (new conexionBD())->conexionPDO();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$conceptos = "('ADMISION','ALUMNO NUEVO','MATRICULA')";

// A. Enlazados al pago de otro concepto.
$malEnlazados = $pdo->query("SELECT i.id_ingreso, i.observacion, i.id_pago_pension AS actual,
        (SELECT q.id_pago_pension FROM pago_pensiones q WHERE q.id_matri = p.id_matri AND q.concepto = i.observacion LIMIT 1) AS correcto,
        (SELECT COUNT(*) FROM pago_pensiones q WHERE q.id_matri = p.id_matri AND q.concepto = i.observacion) AS candidatos
    FROM ingresos i JOIN pago_pensiones p ON p.id_pago_pension = i.id_pago_pension
    WHERE i.observacion IN $conceptos AND p.concepto <> i.observacion")->fetchAll();

// B. Sin pago asociado.
$sinPago = $pdo->query("SELECT i.id_ingreso, i.observacion, i.monto, i.created_at,
        (SELECT GROUP_CONCAT(p.id_pago_pension) FROM pago_pensiones p
          WHERE p.concepto = i.observacion AND p.sub_total = i.monto AND DATE(p.created_at) = i.created_at
            AND NOT EXISTS (SELECT 1 FROM ingresos j WHERE j.id_pago_pension = p.id_pago_pension
                            AND j.observacion = p.concepto)) AS candidatos
    FROM ingresos i WHERE i.id_pago_pension IS NULL AND i.observacion IN $conceptos")->fetchAll();

$reparables = [];
$dudosos = [];
foreach ($malEnlazados as $f) {
    if ((int) $f['candidatos'] === 1) {
        $reparables[] = [$f['id_ingreso'], $f['correcto'], "A: {$f['observacion']} {$f['actual']} → {$f['correcto']}"];
    } else {
        $dudosos[] = "A: ingreso {$f['id_ingreso']} ({$f['observacion']}): {$f['candidatos']} pagos posibles";
    }
}
$usados = [];
foreach ($sinPago as $f) {
    $ids = array_filter(explode(',', (string) $f['candidatos']));
    if (count($ids) === 1 && !isset($usados[$ids[0]])) {
        $usados[$ids[0]] = true;
        $reparables[] = [$f['id_ingreso'], $ids[0], "B: {$f['observacion']} {$f['monto']} del {$f['created_at']} → {$ids[0]}"];
    } else {
        $dudosos[] = "B: ingreso {$f['id_ingreso']} ({$f['observacion']} {$f['monto']} del {$f['created_at']}): "
            . count($ids) . ' pagos posibles';
    }
}

echo count($malEnlazados) . " ingresos enlazados al pago de otro concepto; " . count($sinPago) . " sin pago.\n";
echo count($reparables) . " reparables sin ambigüedad; " . count($dudosos) . " se dejan como están.\n";
foreach ($dudosos as $d) {
    echo "  dudoso  $d\n";
}
if (!$aplicar) {
    foreach ($reparables as [, , $desc]) {
        echo "  reparable  $desc\n";
    }
    echo "\nNada modificado. Revisa la lista y ejecuta con --aplicar (haz un respaldo antes).\n";
    exit(0);
}

$pdo->beginTransaction();
$q = $pdo->prepare('UPDATE ingresos SET id_pago_pension = ? WHERE id_ingreso = ?');
foreach ($reparables as [$ingreso, $pago, $desc]) {
    $q->execute([$pago, $ingreso]);
    echo "  reparado  $desc\n";
}
$pdo->commit();
echo "\n" . count($reparables) . " ingresos reparados.\n";
