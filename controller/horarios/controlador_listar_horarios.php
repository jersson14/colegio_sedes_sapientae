<?php
    require '../../model/model_horarios.php';
    $MHR = new Modelo_Horarios();//Instaciamos
    $consulta = $MHR->Listar_Horarios();
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
