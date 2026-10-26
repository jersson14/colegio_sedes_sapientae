<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_alumnos.php';
// Al escribir el documento del padre o la madre: ¿ya está registrado por un hermano? (Fase 13)
$dni = trim((string) ($_POST['dni'] ?? ''));
if (!preg_match('/^[A-Za-z0-9-]{6,20}$/', $dni)) {
    responder_json(['encontrado' => false]);
}
$excluir = filter_var($_POST['alumno'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$MA = new Modelo_Alumnos();
$r = $MA->Buscar_Padre($dni, $excluir);
responder_json($r ? ['encontrado' => true] + $r : ['encontrado' => false]);
