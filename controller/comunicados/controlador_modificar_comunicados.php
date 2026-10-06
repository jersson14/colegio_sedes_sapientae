<?php
    require_once __DIR__ . '/../../core/guard.php';
    exigir_rol('ADMINISTRADOR');
    require_once __DIR__ . '/../../core/subidas.php';
    require '../../model/model_comunicados.php';
    $MC = new Modelo_Comunicados();//Instaciamos
    //DATOS DE DOCENTE//
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $titulo = strtoupper(htmlspecialchars($_POST['titulo'],ENT_QUOTES,'UTF-8'));
    $descripcion = strtoupper(htmlspecialchars($_POST['descripcion'],ENT_QUOTES,'UTF-8'));
    $esta = strtoupper(htmlspecialchars($_POST['esta'],ENT_QUOTES,'UTF-8'));
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $usu = strtoupper(htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8'));

    // Fase 0.3: el nombre lo genera el servidor; el $nombrefoto del cliente solo indica que hay foto nueva.
    // 'controller/comunicados/fotos/' (sin archivo) es la marca que usa la vista para "sin foto".
    $nueva = !empty($nombrefoto) && $nombrefoto != 'controller/comunicados/fotos/';
    if ($nueva) {
        $nombrefoto = imagen_validada('foto');
        $ruta = 'controller/comunicados/fotos/' . $nombrefoto;
    } elseif (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        $ruta = $nombrefoto;
    }

    $consulta = $MC->Modificar_Comunicado($id, $tipo, $grado, $titulo, $descripcion, $esta, $ruta,$usu);
    echo $consulta;

    if ($consulta == 1 && $nueva) {
        if (imagen_guardar('foto', 'controller/comunicados/fotos', $nombrefoto)) {
            borrar_archivo_subido($fotoactual, 'controller/comunicados/fotos');
        }
    }
?>