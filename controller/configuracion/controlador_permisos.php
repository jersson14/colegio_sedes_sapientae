<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/permisos.php';
// Permisos por rol desde una pantalla (Fase 13): accion=listar | guardar | restablecer. Solo la dirección.
// Se guardan solo las diferencias con los permisos de fábrica; volver a lo de fábrica borra el cambio.
$mapa = permisos_mapa();
$roles = array_values(array_filter(array_keys($mapa['roles']), function ($r) { return $r !== 'ADMINISTRADOR'; }));
$de_direccion = $mapa['roles']['ADMINISTRADOR']['vistas'] ?? [];
$pdo = (new conexionBD())->conexionPDO();
$accion = $_POST['accion'] ?? 'listar';

/** ¿Se puede dar o quitar esa pantalla a ese rol? Las de la dirección, a cualquiera; las propias de un rol, solo a ese rol. */
$editable = function ($rol, $vista) use ($mapa, $de_direccion) {
    if (in_array($vista, PERMISOS_SOLO_DIRECCION, true)) { return false; }
    return in_array($vista, $de_direccion, true) || in_array($vista, $mapa['roles'][$rol]['vistas'] ?? [], true);
};

if ($accion === 'guardar') {
    $rol = (string) ($_POST['rol'] ?? '');
    $vista = (string) ($_POST['vista'] ?? '');
    if (!in_array($rol, $roles, true) || !isset($mapa['pantallas'][$vista])) {
        denegar(400, 'Rol o pantalla no válidos');
    }
    if (!$editable($rol, $vista)) {
        denegar(400, 'Esa pantalla no se puede dar ni quitar a ese rol');
    }
    $permitido = !empty($_POST['permitido']) ? 1 : 0;
    $fabrica = in_array($vista, $mapa['roles'][$rol]['vistas'] ?? [], true) ? 1 : 0;
    if ($permitido === $fabrica) {
        $q = $pdo->prepare("DELETE FROM permisos_pantalla WHERE rol = ? AND vista = ?");
        $q->execute([$rol, $vista]);
    } else {
        $q = $pdo->prepare("INSERT INTO permisos_pantalla(rol, vista, permitido, usu_id, updated_at) VALUES (?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE permitido = VALUES(permitido), usu_id = VALUES(usu_id), updated_at = NOW()");
        $q->execute([$rol, $vista, $permitido, usuario_actual_id()]);
    }
    responder_json(['ok' => true, 'cambio' => $permitido !== $fabrica]);
}

if ($accion === 'restablecer') {
    $rol = (string) ($_POST['rol'] ?? '');
    if ($rol !== '' && !in_array($rol, $roles, true)) {
        denegar(400, 'Rol no válido');
    }
    $q = $rol === '' ? $pdo->prepare("DELETE FROM permisos_pantalla") : $pdo->prepare("DELETE FROM permisos_pantalla WHERE rol = ?");
    $q->execute($rol === '' ? [] : [$rol]);
    responder_json(['ok' => true]);
}

$cambios = [];
foreach ($pdo->query("SELECT rol, vista, permitido FROM permisos_pantalla")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $cambios[$r['rol']][$r['vista']] = (int) $r['permitido'];
}
$pantallas = [];
foreach ($mapa['pantallas'] as $vista => $p) {
    $fila = ['vista' => $vista, 'titulo' => $p['titulo'], 'seccion' => $p['seccion'], 'direccion' => in_array($vista, $de_direccion, true), 'roles' => []];
    foreach ($roles as $rol) {
        $fabrica = in_array($vista, $mapa['roles'][$rol]['vistas'] ?? [], true);
        $c = $cambios[$rol][$vista] ?? null;
        $fila['roles'][$rol] = ['fabrica' => $fabrica, 'actual' => $c === null ? $fabrica : (bool) $c, 'cambio' => $c !== null, 'editable' => $editable($rol, $vista)];
    }
    $pantallas[] = $fila;
}
responder_json(['ok' => true, 'roles' => $roles, 'pantallas' => $pantallas]);
