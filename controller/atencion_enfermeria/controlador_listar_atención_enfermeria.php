<?php
    require '../../model/model_atencion_enfer.php';
    $MAEN = new Modelo_Atencion_Enfer();//Instaciamos
    $consulta = $MAEN->Listar_Atencion_Enfermeria();
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
