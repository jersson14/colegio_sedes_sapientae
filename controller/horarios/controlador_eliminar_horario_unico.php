<?php
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MHR->Eliminar_horario_unico($id);
    echo $consulta;



?>