<?php
    require '../../model/model_seccion.php';
    $MSE = new Modelo_Secciones();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $seccion = strtoupper(htmlspecialchars($_POST['seccion'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $MSE->Modificar_Seccion($id,$seccion,$descrip,$esta);
    echo $consulta;



?>