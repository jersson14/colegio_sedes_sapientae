<?php
    require '../../model/model_empleado.php';
    $ME = new Modelo_Empleado();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $nro = strtoupper(htmlspecialchars($_POST['nro'],ENT_QUOTES,'UTF-8'));
    $nom = strtoupper(htmlspecialchars($_POST['nom'],ENT_QUOTES,'UTF-8'));
    $apepa = strtoupper(htmlspecialchars($_POST['apepa'],ENT_QUOTES,'UTF-8'));
    $apema = strtoupper(htmlspecialchars($_POST['apema'],ENT_QUOTES,'UTF-8'));
    $fnac = strtoupper(htmlspecialchars($_POST['fnac'],ENT_QUOTES,'UTF-8'));
    $movil = strtoupper(htmlspecialchars($_POST['movil'],ENT_QUOTES,'UTF-8'));
    $dire = strtoupper(htmlspecialchars($_POST['dire'],ENT_QUOTES,'UTF-8'));
    $email = htmlspecialchars($_POST['email'],ENT_QUOTES,'UTF-8');
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));

    $consulta = $ME->Modificar_Empleado($id,$nro,$nom,$apepa,$apema,$fnac,$movil,$dire,$email,$esta);
    echo $consulta;



?>