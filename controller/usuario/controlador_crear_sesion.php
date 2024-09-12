<?php
    session_start();
    $idusuario = htmlspecialchars($_POST['idusuario'],ENT_QUOTES,'UTF-8');
    $usuario = htmlspecialchars($_POST['usuario'],ENT_QUOTES,'UTF-8');
    $nombres = htmlspecialchars($_POST['nombres'],ENT_QUOTES,'UTF-8');
    $solonombres = htmlspecialchars($_POST['solonombres'],ENT_QUOTES,'UTF-8');

    $rol = htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8');
    $foto = htmlspecialchars($_POST['foto'],ENT_QUOTES,'UTF-8');


    $movil = htmlspecialchars($_POST['movil'],ENT_QUOTES,'UTF-8');
    $direc = htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8');
    $fechanac = htmlspecialchars($_POST['fechanac'],ENT_QUOTES,'UTF-8');
    $email = htmlspecialchars($_POST['email'],ENT_QUOTES,'UTF-8');
    $dni = htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8');

    $_SESSION['S_ID']=$idusuario;
    $_SESSION['S_USU']=$usuario;
    $_SESSION['S_NOMBRE']=$nombres;
    $_SESSION['S_COMPLETO']=$solonombres;
    $_SESSION['S_ROL']=$rol;
    $_SESSION['S_FOTO']=$foto;

    $_SESSION['S_MOVIL']=$movil;
    $_SESSION['S_DIRECCION']=$direc;
    $_SESSION['S_FECHANACIMIENTO']=$fechanac;
    $_SESSION['S_EMAIL']=$email;
    $_SESSION['S_DNI']=$dni;


?>