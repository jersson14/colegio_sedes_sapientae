<?php
    require '../../model/model_aulas.php';
    $MAU = new Modelo_Aulas();//Instaciamos
    $nivel = htmlspecialchars($_POST['nivel'],ENT_QUOTES,'UTF-8');

    $consulta = $MAU->Listar_aulas_filtro($nivel);
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
