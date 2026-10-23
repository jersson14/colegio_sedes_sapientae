<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    //DATOS DE DOCENTE//
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $dni = documento_post('dni', true, null);
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apelli = strtoupper(htmlspecialchars($_POST['apelli'],ENT_QUOTES,'UTF-8'));
    $espe = strtoupper(htmlspecialchars($_POST['espe'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = telefono_post('telf', true, 'celular');
    $telfal = telefono_post('telfal', false, 'teléfono alterno');
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');
    $nombrefoto = preparar_foto_subida('foto', htmlspecialchars($_POST['nombrefoto'] ?? '',ENT_QUOTES,'UTF-8'));
    if (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        if ($nombrefoto == 'controller/docentes/fotos/') {
            $ruta = $nombrefoto; // Simplemente usa el nombre sin modificarlo
        } else {
            $ruta = 'controller/docentes/fotos/' . $nombrefoto; // Construye la ruta completa para la nueva foto
        }
    }
    
    if (!empty($nombrefoto)) {
        if ($nombrefoto != 'controller/docentes/fotos/' && move_uploaded_file($_FILES['foto']['tmp_name'], "fotos/" . $nombrefoto)) {
            $ruta = 'controller/docentes/fotos/' . $nombrefoto;
        } else {
            $ruta = $fotoactual;
        }
    }
    
    $consulta = $MDO->Modificar_Docentes($id,$dni, $nombre, $apelli, $espe, $sexo, $fechanaci, $telf, $telfal, $direc, $esta, $ruta);
    echo $consulta;
    
    if ($consulta == 1) {
        if (!empty($nombrefoto) && $nombrefoto != 'controller/docentes/fotos/') {
            if ($ruta != $fotoactual) {
                borrar_archivo_seguro($fotoactual, 'controller/docentes/fotos');
            }
        }
    }
?>