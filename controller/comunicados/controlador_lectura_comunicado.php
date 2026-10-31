<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_lecturas.php';
// Qué familias leyeron un comunicado y cuáles no (con su celular para volver a avisar). Solo la dirección.
$M = new Modelo_Lecturas();
$r = $M->Lectura(entero_post('id', 1, 'comunicado'));
if (!$r) {
    denegar(404, 'El comunicado no existe');
}
responder_json(['ok' => true] + $r);
