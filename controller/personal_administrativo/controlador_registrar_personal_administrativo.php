<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/claves.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
    require '../../model/model_personal_admin.php';
    $MPAD = new Modelo_Personal_Administrativo();//Instaciamos
    //DATOS DE DOCENTE//
    $dni = documento_post('dni', true, null);
    $nombre = strtoupper(htmlspecialchars($_POST['nombre'],ENT_QUOTES,'UTF-8'));
    $apelli = strtoupper(htmlspecialchars($_POST['apelli'],ENT_QUOTES,'UTF-8'));
    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $sexo = strtoupper(htmlspecialchars($_POST['sexo'],ENT_QUOTES,'UTF-8'));
    $fechanaci = strtoupper(htmlspecialchars($_POST['fechanaci'],ENT_QUOTES,'UTF-8'));
    $telf = telefono_post('telf', false, 'celular');
    $telfal = telefono_post('telfal', false, 'teléfono alterno');
    $direc = strtoupper(htmlspecialchars($_POST['direc'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = preparar_foto_subida('foto', htmlspecialchars($_POST['nombrefoto'] ?? '',ENT_QUOTES,'UTF-8'));

    //DATOS DEL USUARIO //
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');
    $contra = clave_post('contra', $usu);   // mínimo 8 caracteres; es temporal: se cambia en el primer ingreso
    $email = email_post('email', true, 'correo del usuario');
    $rol = htmlspecialchars($_POST['rol'],ENT_QUOTES,'UTF-8');


    $ruta='controller/personal_administrativo/fotos/'.$nombrefoto;
    $consulta = $MPAD->Registrar_Personal($dni,$nombre,$apelli,$tipo,$sexo,$fechanaci,$telf,$telfal,$direc,$ruta,$usu,$contra,$email,$rol);
    if ($consulta) {
        if($nombrefoto!=""){
            move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto);
        }
        if ($consulta == 1) { marcar_clave_temporal(null, trim($usu)); }
        echo $consulta;
    }
?>