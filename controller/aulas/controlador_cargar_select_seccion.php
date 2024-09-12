<?php
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $consulta = $MAU->Cargar_Select_Seccion();
    echo json_encode($consulta);
 
?>
