<?php
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos    $nota = strtoupper(htmlspecialchars($_POST['nota'],ENT_QUOTES,'UTF-8'));
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $nota = strtoupper(htmlspecialchars($_POST['nota'],ENT_QUOTES,'UTF-8'));
    $obser = strtoupper(htmlspecialchars($_POST['obser'],ENT_QUOTES,'UTF-8'));

    $consulta = $MTA->Registrar_calificación($id,$nota,$obser);
    echo $consulta;



?>