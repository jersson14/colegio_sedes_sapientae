<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_alumnos.php';
// Activa o desactiva el acceso al sistema de un estudiante (p. ej. retirado); su historial se conserva
$id = entero_post('id', 1, 'estudiante');
$estado = $_POST['estado'] ?? '';
if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
    denegar(400, 'Estado inválido');
}
$n = (new Modelo_Alumnos())->Cambiar_Acceso($id, $estado);
responder_json(['ok' => true, 'usuarios' => $n]);
