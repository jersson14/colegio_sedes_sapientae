<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/parametros.php';
// Parámetros del sistema (Fase 13): accion=listar | guardar. Solo la dirección.
$def = parametros_definicion();
if (($_POST['accion'] ?? 'listar') === 'guardar') {
    $nuevos = [];
    foreach ($def as $k => [$defecto, $tipo, $min, $max]) {
        $v = trim((string) ($_POST[$k] ?? ''));
        if ($tipo === 'booleano') {
            $nuevos[$k] = $v === '1' ? '1' : '0';
        } elseif ($tipo === 'entero') {
            if (!preg_match('/^\d+$/', $v) || (int) $v < $min || (int) $v > $max) {
                denegar(400, 'Valor no válido en ' . $k . ' (entre ' . $min . ' y ' . $max . ')');
            }
            $nuevos[$k] = (string) (int) $v;
        } else {
            if (mb_strlen($v, 'UTF-8') < $min || mb_strlen($v, 'UTF-8') > $max) {
                denegar(400, 'Escriba un nombre de 1 a ' . $max . ' letras para cada marca de asistencia');
            }
            $nuevos[$k] = $v;
        }
    }
    $q = (new conexionBD())->conexionPDO()->prepare("INSERT INTO parametros(clave, valor, updated_at) VALUES (?, ?, NOW())
                                                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");
    foreach ($nuevos as $k => $v) { $q->execute([$k, $v]); }
    responder_json(['ok' => true, 'valores' => parametros_todos(true)]);
}
$defectos = [];
foreach ($def as $k => $d) { $defectos[$k] = $d[0]; }
responder_json(['ok' => true, 'valores' => parametros_todos(), 'defectos' => $defectos, 'limite_servidor_mb' => parametros_limite_servidor_mb()]);
