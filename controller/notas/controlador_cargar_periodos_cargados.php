<?php
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $consulta = $MNOTAS->Cargar_Periodos_cargados();
    echo json_encode($consulta);
 
?>
