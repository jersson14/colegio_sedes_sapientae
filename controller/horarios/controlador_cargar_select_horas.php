<?php
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');

    $consulta = $MHR->Cargar_horas($id,$año);
    echo json_encode($consulta);
 
?>
