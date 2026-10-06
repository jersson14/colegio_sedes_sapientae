<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    $consulta = $MPAD->Cargar_Select_Roles();
    echo json_encode($consulta);
 
?>
