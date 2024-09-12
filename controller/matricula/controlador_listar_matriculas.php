<?php
    require '../../model/model_matriculas.php';
    $MMAT= new Modelo_Matriculas();//Instaciamos
    $consulta = $MMAT->Listar_Matriculados();
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
