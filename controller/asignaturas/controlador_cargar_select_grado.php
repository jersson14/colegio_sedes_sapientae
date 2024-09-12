<?php
    require '../../model/model_asignaturas.php';
    $MASIG = new Modelo_Asignaturas();//Instaciamos
    $consulta = $MASIG->Cargar_Select_Grados();
    echo json_encode($consulta);
 
?>
