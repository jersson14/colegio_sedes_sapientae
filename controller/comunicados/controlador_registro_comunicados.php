<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_comunicados.php';
    $MC = new Modelo_Comunicados();//Instaciamos
    //DATOS DE COMUNICADO//

    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $titulo = strtoupper(htmlspecialchars($_POST['titulo'],ENT_QUOTES,'UTF-8'));
    $descripcion = strtoupper(htmlspecialchars($_POST['descripcion'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');



    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    if($nombrefoto!=""){
        $nombrefoto = imagen_validada('foto');
    }
    $ruta='controller/comunicados/fotos/'.$nombrefoto;
    $consulta = $MC->Registrar_Comunicado($tipo,$grado,$titulo,$descripcion,$ruta,$usu);
    if ($consulta) {
        if($nombrefoto!=""){
            imagen_guardar('foto','controller/comunicados/fotos',$nombrefoto);
        }
        echo $consulta;
    }
?>