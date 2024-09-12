<?php
    require '../../model/model_pago_pension.php';
    $MPP= new Modelo_Pago_Pension();//Instaciamos
    $consulta = $MPP->Listar_Pago_pension();
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
