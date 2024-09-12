<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();//Instaciamos
    $numero = strtoupper(htmlspecialchars($_POST['numero'],ENT_QUOTES,'UTF-8'));
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));

    $consulta = $MU->Cargar_Select_Datos_Seguimiento($numero,$dni);
    echo json_encode($consulta);
 
?>
