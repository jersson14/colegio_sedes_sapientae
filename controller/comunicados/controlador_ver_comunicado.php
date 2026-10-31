<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_lecturas.php';
// Abre un comunicado (texto e imagen) y registra que el usuario lo leyó (Fase 13).
$M = new Modelo_Lecturas();
$com = $M->Ver(entero_post('id', 1, 'comunicado'), (int) usuario_actual_id(), rol_actual());
if (!$com) {
    denegar(404, 'El comunicado no existe o no está dirigido a usted');
}
responder_json(['ok' => true, 'comunicado' => $com]);
