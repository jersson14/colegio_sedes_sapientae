<?php
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $consulta = $MPE->Listar_Pensiones();
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
