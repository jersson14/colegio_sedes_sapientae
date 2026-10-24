<?php
// Compara vw_estado_pensiones (Fase 7) con SP_SALDO_PENSION (Fase 2, la regla de la pantalla de cobro)
// para cada matrícula × pensión. Uso: php tests/comparar_morosidad.php [base]  → imprime "filas diferencias"
$bd = $argv[1] ?? 'colegio2_test';
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=$bd;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$filas = $pdo->query("SELECT id_matricula, id_pension, vencida, saldo_pension, saldo_mora FROM vw_estado_pensiones")->fetchAll(PDO::FETCH_ASSOC);
$dif = 0;
foreach ($filas as $f) {
    $q = $pdo->prepare("CALL SP_SALDO_PENSION(?, ?)");
    $q->execute([$f['id_matricula'], $f['id_pension']]);
    $s = $q->fetch(PDO::FETCH_ASSOC);
    $q->closeCursor();
    if ((int) $s['vencida'] !== (int) $f['vencida'] || (float) $s['saldo_pension'] !== (float) $f['saldo_pension'] || (float) $s['saldo_mora'] !== (float) $f['saldo_mora']) {
        $dif++;
        fwrite(STDERR, "matricula {$f['id_matricula']} pension {$f['id_pension']}: vista " . json_encode($f) . " sp " . json_encode($s) . "\n");
    }
}
echo count($filas) . ' ' . $dif;
