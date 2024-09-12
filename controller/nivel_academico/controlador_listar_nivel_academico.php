<?php
    require '../../model/model_nivel_academico.php';
    $MNA = new Modelo_Nivel_Academico();//Instaciamos
    $consulta = $MNA->Listar_Nivel_Academico();
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
