<?php
    require '../../model/model_ingresos.php';
    $MING = new Modelo_Ingresos();//Instaciamos
    $consulta = $MING->Cargar_Select_Indicadores();
    echo json_encode($consulta);
 
?>
