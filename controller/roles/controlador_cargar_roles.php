<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_roles.php';
    $MRO = new Modelo_Roles();//Instaciamos
    $consulta = $MRO->Cargar_Select_Roles();
    echo json_encode($consulta);
 
?>
