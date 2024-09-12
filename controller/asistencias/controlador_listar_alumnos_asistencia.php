<?php
require '../../model/model_asistencia.php';
$MASIS = new Modelo_Asistencia();
    $fecha = htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8');
    $aula = htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8');

    $consulta = $MASIS->Listar_alumnos_asistencia2($fecha,$aula);
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
