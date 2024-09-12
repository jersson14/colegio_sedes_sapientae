<?php
    require '../../model/model_empleado.php';
    $ME = new Modelo_Empleado();//Instaciamos
    $consulta = $ME->Listar_Empleado();
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
