<?php
    require '../../model/model_asignaturas.php';
    $MASIG = new Modelo_Asignaturas();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MASIG->Eliminar_Asignatura($id);
    echo $consulta;



?>