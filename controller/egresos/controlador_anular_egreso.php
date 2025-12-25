<?php
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8')); 
    $obser = strtoupper(htmlspecialchars($_POST['obser'],ENT_QUOTES,'UTF-8'));
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));

    $consulta = $MEGR->Anular_Egreso($id,$obser,$usu);
    echo $consulta;



?>