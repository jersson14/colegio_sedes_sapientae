<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $consulta = $MNOTAS->Cargar_Periodos();
    echo json_encode($consulta);
 
?>
