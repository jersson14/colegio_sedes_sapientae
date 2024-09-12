<?php
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $consulta = $MPAD->Eliminar_Personal($id);
    echo $consulta;
?>