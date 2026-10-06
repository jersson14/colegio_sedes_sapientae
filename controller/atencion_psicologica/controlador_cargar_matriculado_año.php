<?php
    require_once __DIR__ . '/../../core/guard.php';
    require '../../model/model_atencion_psico.php';
    $MAPSI = new Modelo_Atencion_Psico();//Instaciamos
    $consulta = $MAPSI->Cargar_Select_Matriculados();
    echo json_encode($consulta);
 
?>
