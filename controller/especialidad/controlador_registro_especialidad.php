<?php
    require '../../model/model_especialidad.php';
    $MES = new Modelo_Especialidad();//Instaciamos
    $especialidad = strtoupper(htmlspecialchars($_POST['especialidad'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MES->Registrar_Especialidad($especialidad,$descrip);
    echo $consulta;



?>