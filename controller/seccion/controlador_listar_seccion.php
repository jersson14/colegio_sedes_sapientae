<?php
    require '../../model/model_seccion.php';
    $MSE = new Modelo_Secciones();//Instaciamos
    $consulta = $MSE->Listar_Secciones();
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
