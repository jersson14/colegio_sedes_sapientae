<?php
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $nivel = strtoupper(htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8'));
    $mes = strtoupper(htmlspecialchars($_POST['mes'],ENT_QUOTES,'UTF-8'));
    $fecha = strtoupper(htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8'));
    $precio = strtoupper(htmlspecialchars($_POST['precio'],ENT_QUOTES,'UTF-8'));
    $mora = strtoupper(htmlspecialchars($_POST['mora'],ENT_QUOTES,'UTF-8'));

    $consulta = $MPE->Modificar_Pensiones($id,$nivel,$mes,$fecha,$precio,$mora);
    echo $consulta;



?>