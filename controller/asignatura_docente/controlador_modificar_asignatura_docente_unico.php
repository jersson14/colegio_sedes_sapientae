<?php
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $curso = strtoupper(htmlspecialchars($_POST['curso'],ENT_QUOTES,'UTF-8'));

    $consulta = $MASD->Modificar_asig_docente_unico($id,$curso);
    echo $consulta;



?>