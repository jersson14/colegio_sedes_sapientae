<?php
    require '../../model/model_area.php';
    $MA = new Modelo_Area();//Instaciamos
    $consulta = $MA->Listar_Area();
    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
            "sEcho": 1,
            "iTotalRecords": "0",
            "iTotalDisplayRecords": "0",
            "aaData": []
        }';
    }
?>
