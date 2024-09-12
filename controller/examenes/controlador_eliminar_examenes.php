<?php
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));


    $consulta = $MEXA->Eliminar_Examen($id);
    echo $consulta;



?>