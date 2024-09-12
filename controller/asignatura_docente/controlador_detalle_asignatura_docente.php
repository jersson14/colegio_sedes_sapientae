<?php
    require '../../model/model_asignatura_docente.php';

    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $asignatura = htmlspecialchars($_POST['asignatura'],ENT_QUOTES,'UTF-8');
    //convertimos los datos a arreglos con explode()
    $array_asignatura=explode(",",$asignatura);
    for ($i=0; $i < count($array_asignatura); $i++) { 
        $consulta = $MASD->Registrar_detalle_AsignDocente($id,$array_asignatura[$i]);
        echo $consulta;
    }   
?>