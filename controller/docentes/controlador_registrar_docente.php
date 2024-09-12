<?php
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    //DATOS DE DOCENTE//
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apelli = strtoupper(htmlspecialchars($_POST['apelli'],ENT_QUOTES,'UTF-8'));
    $espe = strtoupper(htmlspecialchars($_POST['espe'],ENT_QUOTES,'UTF-8'));
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


    $ruta='controller/docentes/fotos/'.$nombrefoto;
    $consulta = $MDO->Registrar_Docentes($dni,$nombre,$apelli,$espe,$sexo,$fechanaci,$telf,$telfal,$direc,$ruta,$usu,$contra,$email);
    if ($consulta) {
        if($nombrefoto!=""){
            move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto);
        }
        echo $consulta;
    }
?>