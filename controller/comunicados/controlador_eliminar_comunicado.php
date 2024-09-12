<?php
    require '../../model/model_comunicados.php';
    $MC = new Modelo_Comunicados();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MC->Eliminar_Comunicado($id);
    echo $consulta;



?>