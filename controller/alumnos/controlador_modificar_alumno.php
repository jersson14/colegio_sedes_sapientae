<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    //DATOS DE ESTUDIANTE//
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apepa = strtoupper(htmlspecialchars($_POST['apepa'],ENT_QUOTES,'UTF-8'));
    $apema = strtoupper(htmlspecialchars($_POST['apema'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = strtoupper(htmlspecialchars($_POST['telf'],ENT_QUOTES,'UTF-8'));
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');

    //DATOS DE LOS PAPAS //
    $idpa = strtoupper(htmlspecialchars($_POST['idpa'],ENT_QUOTES,'UTF-8'));
    $dnipa = strtoupper(htmlspecialchars($_POST['dnipa'],ENT_QUOTES,'UTF-8'));
    $nompa = strtoupper(htmlspecialchars($_POST['nompa'],ENT_QUOTES,'UTF-8'));
    $celpa = strtoupper(htmlspecialchars($_POST['celpa'],ENT_QUOTES,'UTF-8'));
    $dnima = strtoupper(htmlspecialchars($_POST['dnima'],ENT_QUOTES,'UTF-8'));
    $nomma = strtoupper(htmlspecialchars($_POST['nomma'],ENT_QUOTES,'UTF-8'));
    $celma = strtoupper(htmlspecialchars($_POST['celma'],ENT_QUOTES,'UTF-8'));

    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    // 'controller/alumnos/fotos/' (sin archivo) es la marca que usa la vista para "sin foto".
    $nueva = !empty($nombrefoto) && $nombrefoto != 'controller/alumnos/fotos/';
    if ($nueva) {
        $nombrefoto = imagen_validada('foto');
        $ruta = 'controller/alumnos/fotos/' . $nombrefoto;
    } elseif (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        $ruta = $nombrefoto;
    }

    $consulta = $MALU->Modificar_alumno($id,$dni,$nombre,$apepa,$apema,$sexo,$fechanaci,$telf,$direc,$ruta,$idpa,$dnipa,$nompa,$celpa,$dnima,$nomma,$celma);
    echo $consulta;

    if ($consulta == 1 && $nueva) {
        if (imagen_guardar('foto', 'controller/alumnos/fotos', $nombrefoto)) {
            borrar_archivo_subido($fotoactual, 'controller/alumnos/fotos');
        }
    }
?>