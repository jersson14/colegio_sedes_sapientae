<?php
    require '../../model/model_roles.php';
    $MRO = new Modelo_Roles();//Instaciamos
    $consulta = $MRO->Listar_Roles();
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
