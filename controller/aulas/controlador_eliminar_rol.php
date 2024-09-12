<?php
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MAU->Eliminar_Aulas($id);
    echo $consulta;



?>