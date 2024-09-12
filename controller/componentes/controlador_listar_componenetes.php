<?php
    require '../../model/model_componentes.php';
    $MCOM = new Modelo_Componentes();//Instaciamos
    $consulta = $MCOM->Listar_componentes();
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
