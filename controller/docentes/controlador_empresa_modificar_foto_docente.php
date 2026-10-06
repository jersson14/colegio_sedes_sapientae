<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR', 'DOCENTE');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');

    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    if(empty($nombrefoto)){
        $ruta = 'controller/docentes/fotos/VACIO.png';
    }else{
        $nombrefoto = imagen_validada('foto');
        $ruta = 'controller/docentes/fotos/'.$nombrefoto;
    }

    $consulta = $MDO->Modificar_foto_docente($id,$ruta);
    echo $consulta;
    if ($consulta==1) {
        if(!empty($nombrefoto)){
            if(imagen_guardar('foto','controller/docentes/fotos',$nombrefoto)){
                borrar_archivo_subido($fotoactual,'controller/docentes/fotos');
            }
        }
    }
?>