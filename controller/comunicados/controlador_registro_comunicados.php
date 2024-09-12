<?php
    require '../../model/model_comunicados.php';
    $MC = new Modelo_Comunicados();//Instaciamos
    //DATOS DE COMUNICADO//

    $tipo = strtoupper(htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8'));
    $grado = strtoupper(htmlspecialchars($_POST['grado'],ENT_QUOTES,'UTF-8'));
    $titulo = strtoupper(htmlspecialchars($_POST['titulo'],ENT_QUOTES,'UTF-8'));
    $descripcion = strtoupper(htmlspecialchars($_POST['descripcion'],ENT_QUOTES,'UTF-8'));
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $usu = htmlspecialchars($_POST['usu'],ENT_QUOTES,'UTF-8');



    $ruta='controller/comunicados/fotos/'.$nombrefoto;
    $consulta = $MC->Registrar_Comunicado($tipo,$grado,$titulo,$descripcion,$ruta,$usu);
    if ($consulta) {
        if($nombrefoto!=""){
            move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto);
        }
        echo $consulta;
    }
?>