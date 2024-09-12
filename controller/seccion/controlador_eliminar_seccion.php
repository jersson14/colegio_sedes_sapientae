<?php
    require '../../model/model_seccion.php';
    $MSE = new Modelo_Secciones();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
   

    $consulta = $MSE->Eliminar_Seccion($id);
    echo $consulta;



?>