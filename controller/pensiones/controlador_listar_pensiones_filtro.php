<?php
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $nivel = htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8');

    $consulta = $MPE->Listar_pensiones_filtros($nivel);
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
