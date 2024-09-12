<?php
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    //DATOS DE ESTUDIANTE//
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apepa = strtoupper(htmlspecialchars($_POST['apepa'],ENT_QUOTES,'UTF-8'));
    $apema = strtoupper(htmlspecialchars($_POST['apema'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = strtoupper(htmlspecialchars($_POST['telf'],ENT_QUOTES,'UTF-8'));
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');

    //DATOS DE LOS PAPAS //
    $dnipa = strtoupper(htmlspecialchars($_POST['dnipa'],ENT_QUOTES,'UTF-8'));
    $nompa = strtoupper(htmlspecialchars($_POST['nompa'],ENT_QUOTES,'UTF-8'));
    $celpa = strtoupper(htmlspecialchars($_POST['celpa'],ENT_QUOTES,'UTF-8'));
    $dnima = strtoupper(htmlspecialchars($_POST['dnima'],ENT_QUOTES,'UTF-8'));
    $nomma = strtoupper(htmlspecialchars($_POST['nomma'],ENT_QUOTES,'UTF-8'));
    $celma = strtoupper(htmlspecialchars($_POST['celma'],ENT_QUOTES,'UTF-8'));

    $ruta='controller/alumnos/fotos/'.$nombrefoto;
    $consulta = $MALU->Registrar_Alumnos($dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma);
    if ($consulta) {
        if($nombrefoto!=""){
            move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto);
        }
        echo $consulta;
    }
?>