<?php
    require '../../model/model_nivel_academico.php';
    $MNA = new Modelo_Nivel_Academico();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));

    $consulta = $MNA->Eliminar_Nivel_Academico($id);
    echo $consulta;



?>