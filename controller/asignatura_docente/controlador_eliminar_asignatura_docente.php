<?php
    require '../../model/model_asignatura_docente.php';
    $MASD = new Modelo_Asignatura_Docente();//Instaciamos
    $id = strtoupper(htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8'));
    $consulta = $MASD->Eliminar_asignatura_docente($id);
    echo $consulta;



?>