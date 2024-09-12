<?php
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));
    $rol = strtoupper(htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8'));
    $correo = htmlspecialchars($_POST['correo'],ENT_QUOTES,'UTF-8');

    $consulta = $MU->Modificar_Usuario($id,$usu,$rol,$correo);
    echo $consulta;



?>