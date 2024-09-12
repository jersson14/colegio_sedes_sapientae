<?php
require '../../model/model_asistencia.php';
$MASIS = new Modelo_Asistencia();
    $fecha = strtoupper(htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8'));
    $aula = strtoupper(htmlspecialchars($_POST['aula'],ENT_QUOTES,'UTF-8'));
    
    $consulta = $MASIS->Eliminar_Asistencia($fecha,$aula);
    echo $consulta;



?>