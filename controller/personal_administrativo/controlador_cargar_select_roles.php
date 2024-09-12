<?php
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    $consulta = $MPAD->Cargar_Select_Roles();
    echo json_encode($consulta);
 
?>
