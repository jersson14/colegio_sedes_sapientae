<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    //DATOS DE DOCENTE//
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apelli = strtoupper(htmlspecialchars($_POST['apelli'],ENT_QUOTES,'UTF-8'));
    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = strtoupper(htmlspecialchars($_POST['telf'],ENT_QUOTES,'UTF-8'));
    $telfal = strtoupper(htmlspecialchars($_POST['telfal'],ENT_QUOTES,'UTF-8'));
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');

    //DATOS DEL USUARIO //
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');
    $contra = password_hash(htmlspecialchars($_POST['contra'],ENT_QUOTES,'UTF-8'),PASSWORD_DEFAULT,['cost'=>12]);
    $email = htmlspecialchars($_POST['email'],ENT_QUOTES,'UTF-8');
    $rol = htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8');


    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    if($nombrefoto!=""){
        $nombrefoto = imagen_validada('foto');
    }
    $ruta='controller/personal_administrativo/fotos/'.$nombrefoto;
    $consulta = $MPAD->Registrar_Personal($dni,$nombre,$apelli,$tipo,$sexo,$fechanaci,$telf,$telfal,$direc,$ruta,$usu,$contra,$email,$rol);
    if ($consulta) {
        if($nombrefoto!=""){
            imagen_guardar('foto','controller/personal_administrativo/fotos',$nombrefoto);
        }
        echo $consulta;
    }
?>