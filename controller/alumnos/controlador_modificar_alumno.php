<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    //DATOS DE ESTUDIANTE//
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $dni = documento_post('dni', true, null);
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apepa = strtoupper(htmlspecialchars($_POST['apepa'],ENT_QUOTES,'UTF-8'));
    $apema = strtoupper(htmlspecialchars($_POST['apema'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = telefono_post('telf', false, 'teléfono del alumno');
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');
    $nombrefoto = preparar_foto_subida('foto', htmlspecialchars($_POST['nombrefoto'] ?? '',ENT_QUOTES,'UTF-8'));

    //DATOS DE LOS PAPAS //
    $idpa = strtoupper(htmlspecialchars($_POST['idpa'],ENT_QUOTES,'UTF-8'));
    $dnipa = documento_post('dnipa', false, 'documento del papá');
    $nompa = strtoupper(htmlspecialchars($_POST['nompa'],ENT_QUOTES,'UTF-8'));
    $celpa = telefono_post('celpa', false, 'celular del papá');
    $dnima = documento_post('dnima', true, 'documento de la mamá');
    $nomma = strtoupper(htmlspecialchars($_POST['nomma'],ENT_QUOTES,'UTF-8'));
    $celma = telefono_post('celma', true, 'celular de la mamá');

    if (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        if ($nombrefoto == 'controller/alumnos/fotos/') {
            $ruta = $nombrefoto; // Simplemente usa el nombre sin modificarlo
        } else {
            $ruta = 'controller/alumnos/fotos/' . $nombrefoto; // Construye la ruta completa para la nueva foto
        }
    }
    
    if (!empty($nombrefoto)) {
        if ($nombrefoto != 'controller/alumnos/fotos/' && move_uploaded_file($_FILES['foto']['tmp_name'], "fotos/" . $nombrefoto)) {
            $ruta = 'controller/alumnos/fotos/' . $nombrefoto;
        } else {
            $ruta = $fotoactual;
        }
    }
    $consulta = $MALU->Modificar_alumno($id,$dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$idpa,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma);
    echo $consulta;
    if ($consulta == 1) {
        if (!empty($nombrefoto) && $nombrefoto != 'controller/alumnos/fotos/') {
            if ($ruta != $fotoactual) {
                borrar_archivo_seguro($fotoactual, 'controller/alumnos/fotos');
            }
        }
    }
?>