<?php
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $consulta = $MMAT->Cargar_estudiante();
    echo json_encode($consulta);
 
?>
