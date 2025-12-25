<?php
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $consulta = $MEGR->Cargar_Select_Indicadores_Egresos();
    echo json_encode($consulta);
 
?>
