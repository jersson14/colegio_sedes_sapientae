<?php
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $asig = strtoupper(htmlspecialchars($_POST['asig'],ENT_QUOTES,'UTF-8'));
    $tema = strtoupper(htmlspecialchars($_POST['tema'],ENT_QUOTES,'UTF-8'));
    $fecha = strtoupper(htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEXA->Modificar_Examenes($id,$asig,$tema,$fecha,$descrip);
    echo $consulta;



?>