<?php
/**
 * Validación de periodos (bimestres, trimestres…) antes de guardarlos (Fase 13).
 * Cada periodo: fechas válidas, inicio ≤ fin, dentro de las fechas del año escolar y sin cruzarse con otro.
 * $lista: [['periodo' => 'I BIMESTRE', 'fecha_inicio' => 'Y-m-d', 'fecha_fin' => 'Y-m-d'], ...]
 * Con $con_existentes también se compara con los periodos ya guardados del año (los de otro nombre).
 * Requiere controller/_seguridad.php (denegar).
 */
require_once __DIR__ . '/../model/model_conexion.php';

function validar_periodos($id_anio, array $lista, $con_existentes) {
    $pdo = (new conexionBD())->conexionPDO();
    $q = $pdo->prepare("SELECT `año_escolar` AS anio, fecha_inicio, fecha_fin FROM `año_escolar` WHERE `Id_año_escolar` = ?");
    $q->execute([$id_anio]);
    $anio = $q->fetch(PDO::FETCH_ASSOC);
    if (!$anio) {
        denegar(400, 'Elija un año escolar válido');
    }
    $fecha = function ($t) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return $d && $d->format('Y-m-d') === $t;
    };
    $todos = [];
    foreach ($lista as $p) {
        $nombre = trim((string) ($p['periodo'] ?? ''));
        $ini = (string) ($p['fecha_inicio'] ?? '');
        $fin = (string) ($p['fecha_fin'] ?? '');
        if ($nombre === '' || !$fecha($ini) || !$fecha($fin)) {
            denegar(400, 'Complete el nombre y las fechas de cada periodo');
        }
        if ($ini > $fin) {
            denegar(400, "$nombre: la fecha de inicio es posterior a la de fin");
        }
        if (($anio['fecha_inicio'] && $ini < $anio['fecha_inicio']) || ($anio['fecha_fin'] && $fin > $anio['fecha_fin'])) {
            denegar(400, "$nombre debe estar dentro del año escolar {$anio['anio']} ("
                . date('d/m/Y', strtotime($anio['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($anio['fecha_fin'])) . ')');
        }
        $todos[] = ['nombre' => $nombre, 'ini' => $ini, 'fin' => $fin];
    }
    if ($con_existentes) {
        $nombres = array_column($todos, 'nombre');
        $q = $pdo->prepare("SELECT periodos AS nombre, fecha_inicio AS ini, fecha_fin AS fin FROM periodos WHERE `id_año_escolar` = ?");
        $q->execute([$id_anio]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $e) {
            if (!in_array($e['nombre'], $nombres, true)) { $todos[] = $e; }
        }
    }
    foreach ($todos as $i => $a) {
        foreach ($todos as $j => $b) {
            if ($j > $i && $a['ini'] <= $b['fin'] && $b['ini'] <= $a['fin']) {
                denegar(400, "{$a['nombre']} y {$b['nombre']} se cruzan en sus fechas");
            }
        }
    }
}
