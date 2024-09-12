<?php
    require '../../model/model_area.php';
    $MA = new Modelo_Area();
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $are = strtoupper(htmlspecialchars($_POST['are'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $MA->Modificar_Area($id,$are,$esta);
    echo $consulta;



?>