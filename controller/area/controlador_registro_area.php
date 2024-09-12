<?php
    require '../../model/model_area.php';
    $MA = new Modelo_Area();
    $area = strtoupper(htmlspecialchars($_POST['a'],ENT_QUOTES,'UTF-8'));
    $consulta = $MA->Registrar_Area($area);
    echo $consulta;



?>