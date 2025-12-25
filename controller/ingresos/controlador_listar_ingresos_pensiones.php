<?php
    require '../../model/model_ingresos.php';
    $MING = new Modelo_Ingresos();//Instaciamos
    $consulta = $MING->Listar_Ingresos_pensiones();
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
