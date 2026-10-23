<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();//Instaciamos
    $consulta = $MU->Cargar_Select_Rol();
    echo json_encode($consulta);
 
?>
