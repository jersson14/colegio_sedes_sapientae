<?php
    require '../../model/model_asistencia.php';
    $MASIS = new Modelo_Asistencia();//Instaciamos
    $grado = htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8');

    $consulta = $MASIS->Listar_alumnos_asistencia($grado);
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
