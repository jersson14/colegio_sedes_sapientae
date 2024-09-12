<?php
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    $consulta = $MALU->Listar_Alumnos();
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
