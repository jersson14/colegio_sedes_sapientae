<?php
    require '../../model/model_asistencia.php';
    $MASIS = new Modelo_Asistencia();//Instaciamos
    $consulta = $MASIS->Listar_asitencia();
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
