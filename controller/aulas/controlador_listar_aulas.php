<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $consulta = $MAU->Listar_Aulas();
    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
            "sEcho": 1,
            "iTotalRecords": "0",
            "iTotalDisplayRecords": "0",
            "aaData": [], "data": []
        }';
    }
?>
