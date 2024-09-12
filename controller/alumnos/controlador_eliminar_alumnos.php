<?php
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MALU->Eliminar_Alumno($id);
    echo $consulta;



?>