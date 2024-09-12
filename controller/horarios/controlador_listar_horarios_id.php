<?php
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');

    $consulta = $MHR->Cargar_Id_aula_horarios($id);
    echo json_encode($consulta);
 
?>
