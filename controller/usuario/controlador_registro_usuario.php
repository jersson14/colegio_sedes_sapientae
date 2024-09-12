<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');
    $con = password_hash(htmlspecialchars($_POST['con'],ENT_QUOTES,'UTF-8'),PASSWORD_DEFAULT,['cost'=>12]);
    $ide = strtoupper(htmlspecialchars($_POST['ide'],ENT_QUOTES,'UTF-8'));
    $ida = strtoupper(htmlspecialchars($_POST['ida'],ENT_QUOTES,'UTF-8'));
    $rol = strtoupper(htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8'));

    $consulta = $MU->Registrar_Usuario($usu,$con,$ide,$ida,$rol);
    echo $consulta;



?>