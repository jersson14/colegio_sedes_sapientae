<?php
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $consulta = $MMAT->Cargar_Año();
    echo json_encode($consulta);
 
?>
