<?php
    require '../../model/model_egresos.php';
    $MEGR= new Modelo_Egresos();//Instaciamos
    $indi = htmlspecialchars($_POST['indi'],ENT_QUOTES,'UTF-8');
    $fechaini = htmlspecialchars($_POST['fechaini'],ENT_QUOTES,'UTF-8');
    $fechafin = htmlspecialchars($_POST['fechafin'],ENT_QUOTES,'UTF-8');

    $consulta = $MEGR->Listar_egresos_diversos_filtro($indi,$fechaini,$fechafin);
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
