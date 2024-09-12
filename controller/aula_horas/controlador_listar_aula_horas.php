<?php
    require '../../model/model_aula_horas.php';
    $MAH = new Modelo_aula_Horas();//Instaciamos
    $consulta = $MAH->Listar_aula_horas();
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
