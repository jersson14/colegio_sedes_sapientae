<?php
    require '../../model/model_tipo_documento.php';
    $MTD = new Modelo_Tipo_Documento();//Instaciamos
    $consulta = $MTD->Listar_Tipo_Documento();
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
