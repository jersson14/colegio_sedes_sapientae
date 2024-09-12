<?php
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    $consulta = $MPAD->Listar_Personal_Administrativo();
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
