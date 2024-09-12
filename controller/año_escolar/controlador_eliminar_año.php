<?php
    require '../../model/model_año.php';
    $MANIO = new Modelo_Años();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $consulta = $MANIO->Eliminar_Año($id);
    echo $consulta;



?>