<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $con = password_hash(htmlspecialchars($_POST['con'],ENT_QUOTES,'UTF-8'),PASSWORD_DEFAULT,['cost'=>12]);

    $consulta = $MU->Modificar_Usuario_Contra($id,$con);
    echo $consulta;



?>