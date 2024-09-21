<?php
    require '../../model/model_aula_horas.php';
    $MAH = new Modelo_aula_Horas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');

    $consulta = $MAH->Cargar_Id_aula($id,$año);
    echo json_encode($consulta);
 
?>
