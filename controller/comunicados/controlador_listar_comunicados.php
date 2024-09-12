<?php
    require '../../model/model_comunicados.php';
    $MC = new Modelo_Comunicados();//Instaciamos
    $consulta = $MC->Listar_Comunicados();
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
