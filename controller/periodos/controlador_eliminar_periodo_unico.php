<?php
    require '../../model/model_periodos.php';
    $MPERI = new Modelo_Periodos();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MPERI->Eliminar_periodo_unico($id);
    echo $consulta;



?>