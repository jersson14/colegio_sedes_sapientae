<?php
require '../../model/model_asistencia.php';
$MASIS = new Modelo_Asistencia();
    $año = htmlspecialchars($_POST['año'],ENT_QUOTES,'UTF-8');
    $mes = htmlspecialchars($_POST['mes'],ENT_QUOTES,'UTF-8');
    $aula = htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8');

    $consulta = $MASIS->Listar_alumnos_totales_dia($año,$mes,$aula);
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
