<?php
    require '../../model/model_docentes.php';
    $MDO = new Modelo_Docentes();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');

    if(empty($nombrefoto)){
        $ruta = 'controller/docentes/fotos/VACIO.png';
    }else{
        $ruta = 'controller/docentes/fotos/'.$nombrefoto;
    }

    $consulta = $MDO->Modificar_foto_docente($id,$ruta);
    echo $consulta;
    if ($consulta==1) {
        if(!empty($nombrefoto)){
            if(move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto));
            unlink('../../'.$fotoactual);
        }
    }
?>