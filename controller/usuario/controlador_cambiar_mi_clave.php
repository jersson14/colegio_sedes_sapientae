<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/claves.php';
// Cada usuario cambia su propia contraseña (también la temporal del primer ingreso). Siempre es la de la sesión.
$id = (int) usuario_actual_id();
$pdo = (new conexionBD())->conexionPDO();
$q = $pdo->prepare("SELECT usu_usuario, usu_contra FROM usuario WHERE usu_id = ?");
$q->execute([$id]);
$u = $q->fetch(PDO::FETCH_ASSOC);
if (!$u) {
    denegar(401, 'Sesión no válida');
}
$actual = htmlspecialchars((string) ($_POST['actual'] ?? ''), ENT_QUOTES, 'UTF-8');
if (!password_verify($actual, $u['usu_contra'])) {
    denegar(400, 'La contraseña actual no es correcta');
}
if ((string) ($_POST['nueva'] ?? '') !== (string) ($_POST['confirmar'] ?? '')) {
    denegar(400, 'La nueva contraseña y su confirmación no coinciden');
}
if ((string) ($_POST['nueva'] ?? '') === (string) ($_POST['actual'] ?? '')) {
    denegar(400, 'La nueva contraseña debe ser distinta de la actual');
}
$hash = clave_post('nueva', $u['usu_usuario'], 'La nueva contraseña');
$pdo->prepare("UPDATE usuario SET usu_contra = ?, debe_cambiar_clave = 0, updated_at = NOW() WHERE usu_id = ?")->execute([$hash, $id]);
unset($_SESSION['S_DEBE_CAMBIAR']);
responder_json(['ok' => true]);
