<?php
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $seccion = strtoupper(htmlspecialchars($_POST['seccion'],ENT_QUOTES,'UTF-8'));
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    $consulta = $MAU->Modificar_Aulas($id,$grado,$seccion,$nivel,$descrip,$estatus);
    echo $consulta;



?>