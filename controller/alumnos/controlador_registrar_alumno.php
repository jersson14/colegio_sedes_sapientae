<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    //DATOS DE ESTUDIANTE//
    $dni = documento_post('dni', true, null);
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apepa = strtoupper(htmlspecialchars($_POST['apepa'],ENT_QUOTES,'UTF-8'));
    $apema = strtoupper(htmlspecialchars($_POST['apema'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = telefono_post('telf', false, 'teléfono del alumno');
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = preparar_foto_subida('foto', htmlspecialchars($_POST['nombrefoto'] ?? '',ENT_QUOTES,'UTF-8'));

    //DATOS DE LOS PAPAS //
    $dnipa = documento_post('dnipa', false, 'documento del papá');
    $nompa = strtoupper(htmlspecialchars($_POST['nompa'],ENT_QUOTES,'UTF-8'));
    $celpa = telefono_post('celpa', false, 'celular del papá');
    $dnima = documento_post('dnima', true, 'documento de la mamá');
    $nomma = strtoupper(htmlspecialchars($_POST['nomma'],ENT_QUOTES,'UTF-8'));
    $celma = telefono_post('celma', true, 'celular de la mamá');

    $ruta='controller/alumnos/fotos/'.$nombrefoto;
    $consulta = $MALU->Registrar_Alumnos($dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma);
    if ($consulta) {
        if($nombrefoto!=""){
            move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto);
        }
        echo $consulta;
    }
?>