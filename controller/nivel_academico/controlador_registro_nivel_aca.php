<?php
    require '../../model/model_nivel_academico.php';
    $MNA = new Modelo_Nivel_Academico();//Instaciamos
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MNA->Registrar_Nivel_Academic($nivel,$descrip);
    echo $consulta;



?>