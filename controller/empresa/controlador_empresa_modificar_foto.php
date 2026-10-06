<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_empresa.php';
    $ME = new Modelo_Empresa();
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');

    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    if(empty($nombrefoto)){
        $ruta = 'controller/empresa/FOTOS/loj.jpg';
    }else{
        $nombrefoto = imagen_validada('foto');
        $ruta = 'controller/empresa/FOTOS/'.$nombrefoto;
    }

    $consulta = $ME->Modificar_foto_empresa($id,$ruta);
    echo $consulta;
    if ($consulta==1) {
        if(!empty($nombrefoto)){
            if(imagen_guardar('foto','controller/empresa/FOTOS',$nombrefoto)){
                borrar_archivo_subido($fotoactual,'controller/empresa/FOTOS');
            }
        }
    }
?>