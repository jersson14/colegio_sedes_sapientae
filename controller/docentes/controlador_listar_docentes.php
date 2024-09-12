<?php
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    $consulta = $MDO->Listar_Docentes();
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
