<?php
    require '../../model/model_indicadores.php';
    $MIN = new Modelo_Indicadores();//Instaciamos
    $consulta = $MIN->Listar_Indicadores();
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
