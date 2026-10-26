<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_docentes.php';
// Activa o desactiva a un docente y su usuario (inactivo no ingresa al sistema); su historial se conserva
$id = entero_post('id', 1, 'docente');
$estado = $_POST['estado'] ?? '';
if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
    denegar(400, 'Estado inválido');
}
if ((int) $id === (int) usuario_actual_id()) {
    denegar(400, 'No puede desactivar su propio usuario');
}
responder_json(['ok' => (new Modelo_Docentes())->Cambiar_Estado($id, $estado) === 1]);
