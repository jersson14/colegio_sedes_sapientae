<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_matriculas.php';
    $MMA = new Modelo_Matriculas();
    $b = $MMA->Traer_Beca(entero_post('id', 1, 'matrícula'));
    if (!$b) { denegar(404, 'La matrícula no existe'); }
    responder_json(['ok' => true, 'data' => $b]);
?>
