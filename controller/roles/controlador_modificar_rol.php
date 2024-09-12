<?php
    require '../../model/model_roles.php';
    $MRO = new Modelo_Roles();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $rol = strtoupper(htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $MRO->Modificar_Rol($id,$rol,$descrip,$esta);
    echo $consulta;



?>