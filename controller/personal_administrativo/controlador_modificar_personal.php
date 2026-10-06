<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    //DATOS DE DOCENTE//
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $dni = strtoupper(htmlspecialchars($_POST['dni'],ENT_QUOTES,'UTF-8'));
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apelli = strtoupper(htmlspecialchars($_POST['apelli'],ENT_QUOTES,'UTF-8'));
    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = strtoupper(htmlspecialchars($_POST['telf'],ENT_QUOTES,'UTF-8'));
    $telfal = strtoupper(htmlspecialchars($_POST['telfal'],ENT_QUOTES,'UTF-8'));
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    // 'controller/personal_administrativo/fotos/' (sin archivo) es la marca que usa la vista para "sin foto".
    $nueva = !empty($nombrefoto) && $nombrefoto != 'controller/personal_administrativo/fotos/';
    if ($nueva) {
        $nombrefoto = imagen_validada('foto');
        $ruta = 'controller/personal_administrativo/fotos/' . $nombrefoto;
    } elseif (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        $ruta = $nombrefoto;
    }

    $consulta = $MPAD->Modificar_Personal($id,$dni, $nombre, $apelli, $tipo, $sexo, $fechanaci, $telf, $telfal, $direc, $esta, $ruta);
    echo $consulta;

    if ($consulta == 1 && $nueva) {
        if (imagen_guardar('foto', 'controller/personal_administrativo/fotos', $nombrefoto)) {
            borrar_archivo_subido($fotoactual, 'controller/personal_administrativo/fotos');
        }
    }
?>