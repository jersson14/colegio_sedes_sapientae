<?php
    require '../../model/model_aula_horas.php';
    $MAH = new Modelo_aula_Horas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MAH->Eliminar_horas_aula($id);
    echo $consulta;



?>