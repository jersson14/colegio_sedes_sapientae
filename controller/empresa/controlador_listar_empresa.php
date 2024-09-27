<?php
    require '../../model/model_empresa.php';
    $ME = new Modelo_Empresa();//Instaciamos
    $consulta = $ME->Listar_Empresa();
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
