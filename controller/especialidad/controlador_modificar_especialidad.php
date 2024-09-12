<?php
    require '../../model/model_especialidad.php';
    $MES = new Modelo_Especialidad();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $especialidad = strtoupper(htmlspecialchars($_POST['especialidad'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $MES->Modificar_Especialidad($id,$especialidad,$descrip,$esta);
    echo $consulta;



?>