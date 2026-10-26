<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/claves.php';
    require '../../model/model_usuario.php';
    $MU = new Modelo_Usuario();
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');
    $con = clave_post('con', $usu);   // mínimo 8 caracteres; es temporal: se cambia en el primer ingreso
    $ide = strtoupper(htmlspecialchars($_POST['ide'],ENT_QUOTES,'UTF-8'));
    $ida = strtoupper(htmlspecialchars($_POST['ida'],ENT_QUOTES,'UTF-8'));
    $rol = strtoupper(htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8'));

    $consulta = $MU->Registrar_Usuario($usu,$con,$ide,$ida,$rol);
    if ($consulta == 1) { marcar_clave_temporal(null, trim($usu)); }
    echo $consulta;



?>