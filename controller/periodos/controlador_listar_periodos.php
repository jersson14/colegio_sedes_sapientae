<?php
    require '../../model/model_periodos.php';
    $MPERI = new Modelo_Periodos();//Instaciamos
    $consulta = $MPERI->Listar_Periodos();
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
