<?php
    require '../../model/model_especialidad.php';
    $MES = new Modelo_Especialidad();//Instaciamos
    $consulta = $MES->Listar_Especialidad();
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
