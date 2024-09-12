<?php
    require '../../model/model_asignaturas.php';
    $MASIG = new Modelo_Asignaturas();//Instaciamos
    $consulta = $MASIG->Listar_Asignaturas();
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
