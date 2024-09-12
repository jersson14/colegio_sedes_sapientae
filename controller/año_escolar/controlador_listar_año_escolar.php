<?php
    require '../../model/model_año.php';
    $MANIO = new Modelo_Años();//Instaciamos
    $consulta = $MANIO->Listar_Años();
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
