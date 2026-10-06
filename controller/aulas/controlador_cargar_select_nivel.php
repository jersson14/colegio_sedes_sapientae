<?php
    require_once __DIR__ . '/../../core/guard.php';
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $consulta = $MAU->Cargar_Select_Nivel();
    echo json_encode($consulta);
 
?>
