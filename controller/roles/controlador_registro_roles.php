<?php
    require '../../model/model_roles.php';
    $MRO = new Modelo_Roles();//Instaciamos
    $rol = strtoupper(htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MRO->Registrar_Roles($rol,$descrip);
    echo $consulta;



?>