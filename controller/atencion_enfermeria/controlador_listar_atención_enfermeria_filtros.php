<?php
    require '../../model/model_atencion_enfer.php';
    $MAEN = new Modelo_Atencion_Enfer();//Instaciamos
    $grado = htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8');
    $fechaini = htmlspecialchars($_POST['fechaini'],ENT_QUOTES,'UTF-8');
    $fechafin = htmlspecialchars($_POST['fechafin'],ENT_QUOTES,'UTF-8');

    $consulta = $MAEN->Listar_atenciones_filtros_enfermeria($grado,$fechaini,$fechafin);
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
