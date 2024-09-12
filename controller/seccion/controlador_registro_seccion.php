<?php
    require '../../model/model_seccion.php';
    $MSE = new Modelo_Secciones();//Instaciamos
    $seccion = strtoupper(htmlspecialchars($_POST['seccion'],ENT_QUOTES,'UTF-8'));
    $descrip = strtoupper(htmlspecialchars($_POST['descrip'],ENT_QUOTES,'UTF-8'));

    $consulta = $MSE->Registrar_Seccion($seccion,$descrip);
    echo $consulta;



?>