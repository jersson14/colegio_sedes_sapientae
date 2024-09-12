<?php
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $estatus = strtoupper(htmlspecialchars($_POST['estatus'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEXA->Modificar_Examen_Estatus($id,$estatus);
    echo $consulta;



?>