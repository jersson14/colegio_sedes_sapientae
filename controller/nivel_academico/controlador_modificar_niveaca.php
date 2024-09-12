<?php
    require '../../model/model_nivel_academico.php';
    $MNA = new Modelo_Nivel_Academico();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $MNA->Modificar_Nivel_Academico($id,$nivel,$descrip,$esta);
    echo $consulta;



?>