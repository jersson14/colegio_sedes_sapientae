<?php
    require '../../model/model_asignaturas.php';
    $MASIG = new Modelo_Asignaturas();//Instaciamos
    $asigna = strtoupper(htmlspecialchars($_POST['asigna'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $obse = strtoupper(htmlspecialchars($_POST['obse'],ENT_QUOTES,'UTF-8'));

    $consulta = $MASIG->Registrar_Asignaturas($asigna,$grado,$obse);
    echo $consulta;



?>