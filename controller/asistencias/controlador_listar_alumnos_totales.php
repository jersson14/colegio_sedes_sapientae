<?php
require '../../model/model_asistencia.php';
$MASIS = new Modelo_Asistencia();
    $mes = htmlspecialchars($_POST['mes'],ENT_QUOTES,'UTF-8');
    $aula = htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8');

    $consulta = $MASIS->Listar_alumnos_totales($mes,$aula);
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
