<?php
    require '../../model/model_asignaturas.php';
    $MASIG = new Modelo_Asignaturas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $asigna = strtoupper(htmlspecialchars($_POST['asigna'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $observa = strtoupper(htmlspecialchars($_POST['observa'],ENT_QUOTES,'UTF-8'));

    $consulta = $MASIG->Modificar_Asignaturas($id,$asigna,$grado,$observa);
    echo $consulta;



?>