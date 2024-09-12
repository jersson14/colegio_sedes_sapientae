<?php
    require '../../model/model_atencion_psico.php';
    $MAPSI = new Modelo_Atencion_Psico();//Instaciamos
    $consulta = $MAPSI->Listar_Atencion_Psicologica();
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
