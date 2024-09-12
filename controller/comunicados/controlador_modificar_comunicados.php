<?php
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

    if (empty($nombrefoto)) {
        $ruta = $fotoactual;
    } else {
        if ($nombrefoto == 'controller/comunicados/fotos/') {
            $ruta = $nombrefoto; // Simplemente usa el nombre sin modificarlo
        } else {
            $ruta = 'controller/comunicados/fotos/' . $nombrefoto; // Construye la ruta completa para la nueva foto
        }
    }
    
    if (!empty($nombrefoto)) {
        if ($nombrefoto != 'controller/comunicados/fotos/' && move_uploaded_file($_FILES['foto']['tmp_name'], "fotos/" . $nombrefoto)) {
            $ruta = 'controller/comunicados/fotos/' . $nombrefoto;
        } else {
            $ruta = $fotoactual;
        }
    }
    
    $consulta = $MC->Modificar_Comunicado($id, $tipo, $grado, $titulo, $descripcion, $esta, $ruta,$usu);
    echo $consulta;
    
    if ($consulta == 1) {
        if (!empty($nombrefoto) && $nombrefoto != 'controller/comunicados/fotos/') {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], "fotos/" . $nombrefoto)) {
                unlink('../../' . $fotoactual);
            }
        }
    }
?>