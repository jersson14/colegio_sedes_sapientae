<?php
    require '../../model/model_examenes.php';
    $MEXA = new Modelo_Examenes();//Instaciamos
    $grado = htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8');

    $consulta = $MEXA->Listar_examenes_filtro($grado);
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
