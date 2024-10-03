<?php
    require '../../model/model_alumnos.php';
    $MALU = new Modelo_Alumnos();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $nombrefoto = htmlspecialchars($_POST['nombrefoto'],ENT_QUOTES,'UTF-8');
    $fotoactual = htmlspecialchars($_POST['fotoactual'],ENT_QUOTES,'UTF-8');

    if(empty($nombrefoto)){
        $ruta = 'controller/alumnos/fotos/VACIO.png';
    }else{
        $ruta = 'controller/alumnos/fotos/'.$nombrefoto;
    }

    $consulta = $MALU->Modificar_foto_estudiante($id,$ruta);
    echo $consulta;
    if ($consulta==1) {
        if(!empty($nombrefoto)){
            if(move_uploaded_file($_FILES['foto']['tmp_name'],"fotos/".$nombrefoto));
            unlink('../../'.$fotoactual);
        }
    }
?>