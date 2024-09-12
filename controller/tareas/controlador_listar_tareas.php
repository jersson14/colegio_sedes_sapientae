<?php
    require '../../model/model_tareas.php';
    $MTA = new Modelo_Tareas();//Instaciamos
    $consulta = $MTA->Listar_tareas();
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
