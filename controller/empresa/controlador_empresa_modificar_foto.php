<?php
    require '../../model/model_empresa.php';
    $ME = new Modelo_Empresa();
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');

    if(empty($nombrefoto)){
        $ruta = 'controller/empresa/FOTOS/loj.jpg';
    }else{
        $ruta = 'controller/empresa/FOTOS/'.$nombrefoto;
    }

    $consulta = $ME->Modificar_foto_empresa($id,$ruta);
    echo $consulta;
    if ($consulta==1) {
        if(!empty($nombrefoto)){
            if(move_uploaded_file($_FILES['foto']['tmp_name'],"FOTOS/".$nombrefoto));
            unlink('../../'.$fotoactual);
        }
    }
?>