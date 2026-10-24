<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_reportes.php';
    $MR = new Modelo_Reportes();
    responder_json(['ok' => true] + $MR->Morosidad(entero_post('anio', 1, 'año escolar'), entero_post('nivel', 0, 'nivel'), entero_post('aula', 0, 'aula')));
?>
