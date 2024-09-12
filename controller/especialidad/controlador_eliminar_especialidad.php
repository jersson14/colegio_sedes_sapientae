<?php
    require '../../model/model_especialidad.php';
    $MES = new Modelo_Especialidad();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $consulta = $MES->Eliminar_Especialidad($id);
    echo $consulta;
?>