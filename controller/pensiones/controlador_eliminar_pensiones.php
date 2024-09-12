<?php
    require '../../model/model_pensiones.php';
    $MPE = new Modelo_Pensiones();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));


    $consulta = $MPE->Eliminar_Pensiones($id);
    echo $consulta;



?>