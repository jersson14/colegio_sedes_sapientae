<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
    require '../../model/model_empresa.php';
    $ME = new Modelo_Empresa();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $nom = strtoupper(htmlspecialchars($_POST['nom'],ENT_QUOTES,'UTF-8'));
    $email = strtoupper(email_post('email', true, 'correo del colegio'));
    $cod = strtoupper(htmlspecialchars($_POST['cod'],ENT_QUOTES,'UTF-8'));
    $tel = strtoupper(htmlspecialchars($_POST['tel'],ENT_QUOTES,'UTF-8'));
    $dir = strtoupper(htmlspecialchars($_POST['dir'],ENT_QUOTES,'UTF-8'));
    

    $consulta = $ME->Modificar_Empresa($id,$nom,$email,$cod,$tel,$dir);
    echo $consulta;



?>