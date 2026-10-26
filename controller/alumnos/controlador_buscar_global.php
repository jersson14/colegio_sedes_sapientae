<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_ficha.php';
// Buscador general de la barra superior (Fase 13): estudiantes por nombre o documento
$texto = texto_post('q', 80);
if (!mb_check_encoding($texto, 'UTF-8')) {
    $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');   // teclados o navegadores antiguos
}
if (mb_strlen($texto, 'UTF-8') < 2) {
    responder_json(['data' => []]);
}
$texto = addcslashes($texto, '%_\\');
$M = new Modelo_Ficha();
responder_json(['data' => $M->Buscar($texto)]);
